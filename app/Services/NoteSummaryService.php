<?php

namespace App\Services;

use App\Models\Note;
use Illuminate\Support\Str;

class NoteSummaryService
{
    public function __construct(
        private readonly TextEmbeddingService $embeddings,
        private readonly OpenAIClient $openAI,
    ) {}

    public function summarize(Note $note): string
    {
        $openAISummary = $this->openAI->summarize($note->title, $note->content);

        if (filled($openAISummary)) {
            return Str::limit($openAISummary, 500);
        }

        $sentences = $this->sentences($note->content);

        if ($sentences === []) {
            return Str::limit($note->content, 220);
        }

        $keywords = $this->embeddings->keywords($note->title.' '.$note->content, 6);
        $ranked = collect($sentences)
            ->map(fn (string $sentence): array => [
                'sentence' => $sentence,
                'score' => $this->sentenceScore($sentence, $keywords),
            ])
            ->sortByDesc('score')
            ->take(2)
            ->pluck('sentence')
            ->all();

        $summary = implode(' ', $ranked);

        return Str::limit($summary !== '' ? $summary : $sentences[0], 320);
    }

    /**
     * @return array<int, string>
     */
    private function sentences(string $content): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($content)) ?: [];

        return array_values(array_filter(
            array_map('trim', $sentences),
            static fn (string $sentence): bool => $sentence !== '',
        ));
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function sentenceScore(string $sentence, array $keywords): int
    {
        $score = Str::length($sentence) > 220 ? -1 : 0;

        foreach ($keywords as $keyword) {
            if (Str::contains(Str::lower($sentence), $keyword)) {
                $score += 3;
            }
        }

        return $score + min(5, str_word_count($sentence));
    }
}
