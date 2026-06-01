<?php

namespace App\Services;

use Illuminate\Support\Str;

class TextEmbeddingService
{
    private const DIMENSIONS = 96;

    public function __construct(private readonly OpenAIClient $openAI)
    {
    }

    /**
     * Build a deterministic normalized vector that gives this assignment a
     * local semantic-search path without requiring external API credentials.
     *
     * @return array<int, float>
     */
    public function embed(string $text): array
    {
        $openAIEmbedding = $this->openAI->embedding($text);

        if ($openAIEmbedding !== null) {
            return $openAIEmbedding;
        }

        $vector = array_fill(0, self::DIMENSIONS, 0.0);

        foreach ($this->tokens($text) as $token) {
            $index = abs(crc32($token)) % self::DIMENSIONS;
            $vector[$index] += 1.0;

            foreach ($this->characterBigrams($token) as $bigram) {
                $bigramIndex = abs(crc32($bigram)) % self::DIMENSIONS;
                $vector[$bigramIndex] += 0.35;
            }
        }

        return $this->normalize($vector);
    }

    /**
     * @param  array<int, float>|null  $left
     * @param  array<int, float>|null  $right
     */
    public function cosineSimilarity(?array $left, ?array $right): float
    {
        if ($left === null || $right === null) {
            return 0.0;
        }

        $score = 0.0;

        foreach ($left as $index => $value) {
            $score += $value * ($right[$index] ?? 0.0);
        }

        return round($score, 6);
    }

    /**
     * @return array<int, string>
     */
    public function keywords(string $text, int $limit = 8): array
    {
        $stopWords = array_flip([
            'about', 'after', 'again', 'also', 'and', 'are', 'because', 'but', 'for',
            'from', 'has', 'have', 'into', 'not', 'our', 'that', 'the', 'their',
            'this', 'was', 'were', 'with', 'you', 'your',
        ]);

        $counts = [];

        foreach ($this->tokens($text) as $token) {
            if (isset($stopWords[$token]) || Str::length($token) < 3) {
                continue;
            }

            $counts[$token] = ($counts[$token] ?? 0) + 1;
        }

        arsort($counts);

        return array_slice(array_keys($counts), 0, $limit);
    }

    /**
     * @return array<int, string>
     */
    private function tokens(string $text): array
    {
        preg_match_all('/[\pL\pN]+/u', Str::lower($text), $matches);

        return $matches[0] ?? [];
    }

    /**
     * @return array<int, string>
     */
    private function characterBigrams(string $token): array
    {
        $bigrams = [];
        $length = Str::length($token);

        for ($index = 0; $index < $length - 1; $index++) {
            $bigrams[] = Str::substr($token, $index, 2);
        }

        return $bigrams;
    }

    /**
     * @param  array<int, float>  $vector
     * @return array<int, float>
     */
    private function normalize(array $vector): array
    {
        $magnitude = sqrt(array_sum(array_map(
            static fn (float $value): float => $value * $value,
            $vector,
        )));

        if ($magnitude === 0.0) {
            return $vector;
        }

        return array_map(
            static fn (float $value): float => round($value / $magnitude, 8),
            $vector,
        );
    }
}
