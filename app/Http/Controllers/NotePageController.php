<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use App\Services\NoteSummaryService;
use App\Services\TextEmbeddingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotePageController extends Controller
{
    public function __construct(private readonly TextEmbeddingService $embeddings) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'min:2', 'max:180'],
        ]);

        $query = $validated['q'] ?? null;

        if ($query) {
            $queryEmbedding = $this->embeddings->embed($query);
            $queryTerms = $this->embeddings->keywords($query, 12);

            $notes = Note::query()
                ->latest()
                ->get()
                ->map(fn (Note $note): array => [
                    'score' => $this->searchScore($queryEmbedding, $queryTerms, $note),
                    'note' => $note,
                ])
                ->filter(fn (array $result): bool => $result['score'] > 0)
                ->sortByDesc('score')
                ->values();

            return view('notes.index', [
                'notes' => $notes,
                'query' => $query,
                'isSearch' => true,
            ]);
        }

        return view('notes.index', [
            'notes' => Note::query()->latest()->paginate(10),
            'query' => null,
            'isSearch' => false,
        ]);
    }

    public function create(): View
    {
        return view('notes.create');
    }

    public function store(StoreNoteRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['embedding'] = $this->embeddings->embed($data['title'].' '.$data['content']);

        $note = Note::query()->create($data);

        return redirect()
            ->route('notes.show', $note)
            ->with('status', 'Note created.');
    }

    public function show(Note $note): View
    {
        return view('notes.show', ['note' => $note]);
    }

    public function edit(Note $note): View
    {
        return view('notes.edit', ['note' => $note]);
    }

    public function update(UpdateNoteRequest $request, Note $note): RedirectResponse
    {
        $data = $request->validated();
        $title = $data['title'] ?? $note->title;
        $content = $data['content'] ?? $note->content;

        $data['embedding'] = $this->embeddings->embed($title.' '.$content);

        if (array_key_exists('content', $data)) {
            $data['summary'] = null;
        }

        $note->update($data);

        return redirect()
            ->route('notes.show', $note)
            ->with('status', 'Note updated.');
    }

    public function destroy(Note $note): RedirectResponse
    {
        $note->delete();

        return redirect()
            ->route('notes.index')
            ->with('status', 'Note deleted.');
    }

    public function summary(Note $note, NoteSummaryService $summaries): RedirectResponse
    {
        $note->update([
            'summary' => $summaries->summarize($note),
        ]);

        return redirect()
            ->route('notes.show', $note)
            ->with('status', 'Summary generated.');
    }

    /**
     * @param  array<int, float>  $queryEmbedding
     * @param  array<int, string>  $queryTerms
     */
    private function searchScore(array $queryEmbedding, array $queryTerms, Note $note): float
    {
        $semanticScore = $this->embeddings->cosineSimilarity($queryEmbedding, $note->embedding);
        $haystack = str($note->title.' '.$note->content)->lower();
        $matchedTerms = 0;

        foreach ($queryTerms as $term) {
            if ($haystack->contains($term)) {
                $matchedTerms++;
            }
        }

        $keywordScore = $queryTerms === [] ? 0.0 : $matchedTerms / count($queryTerms);
        $score = ($semanticScore * 0.7) + ($keywordScore * 0.3);

        return round(min(1.0, $score), 6);
    }
}
