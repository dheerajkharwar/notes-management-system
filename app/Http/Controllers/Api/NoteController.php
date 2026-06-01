<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use App\Services\NoteSummaryService;
use App\Services\TextEmbeddingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class NoteController extends Controller
{
    public function __construct(private readonly TextEmbeddingService $embeddings) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $notes = Note::query()
            ->latest()
            ->paginate($validated['limit'] ?? 10);

        return NoteResource::collection($notes);
    }

    public function store(StoreNoteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['embedding'] = $this->embeddings->embed($data['title'].' '.$data['content']);

        $note = Note::query()->create($data);

        return (new NoteResource($note))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Note $note): NoteResource
    {
        return new NoteResource($note);
    }

    public function update(UpdateNoteRequest $request, Note $note): NoteResource
    {
        $data = $request->validated();
        $nextTitle = Arr::get($data, 'title', $note->title);
        $nextContent = Arr::get($data, 'content', $note->content);

        $data['embedding'] = $this->embeddings->embed($nextTitle.' '.$nextContent);

        if (array_key_exists('content', $data)) {
            $data['summary'] = null;
        }

        $note->update($data);

        return new NoteResource($note->refresh());
    }

    public function destroy(Note $note): JsonResponse
    {
        $note->delete();

        return response()->json(null, 204);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:180'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:25'],
        ]);

        $queryEmbedding = $this->embeddings->embed($validated['q']);
        $queryTerms = $this->embeddings->keywords($validated['q'], 12);
        $limit = $validated['limit'] ?? 10;

        $results = Note::query()
            ->latest()
            ->get()
            ->map(fn (Note $note): array => [
                'score' => $this->searchScore($queryEmbedding, $queryTerms, $note),
                'note' => new NoteResource($note),
            ])
            ->filter(fn (array $result): bool => $result['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();

        return response()->json([
            'data' => $results,
            'meta' => [
                'query' => $validated['q'],
                'count' => $results->count(),
                'method' => 'local-hashed-embedding',
            ],
        ]);
    }

    public function summary(Note $note, NoteSummaryService $summaries): NoteResource
    {
        $note->update([
            'summary' => $summaries->summarize($note),
        ]);

        return new NoteResource($note->refresh());
    }

    /**
     * @param  array<int, float>  $queryEmbedding
     * @param  array<int, string>  $queryTerms
     */
    private function searchScore(array $queryEmbedding, array $queryTerms, Note $note): float
    {
        $semanticScore = $this->embeddings->cosineSimilarity($queryEmbedding, $note->embedding);
        $haystack = Str::lower($note->title.' '.$note->content);
        $matchedTerms = 0;

        foreach ($queryTerms as $term) {
            if (Str::contains($haystack, $term)) {
                $matchedTerms++;
            }
        }

        $keywordScore = $queryTerms === [] ? 0.0 : $matchedTerms / count($queryTerms);
        $score = ($semanticScore * 0.7) + ($keywordScore * 0.3);

        return round(min(1.0, $score), 6);
    }
}
