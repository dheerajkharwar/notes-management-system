<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OpenAIClient
{
    public function enabled(): bool
    {
        return filled(config('services.openai.key'));
    }

    /**
     * @return array<int, float>|null
     */
    public function embedding(string $text): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $response = $this->client()
                ->post('/embeddings', [
                    'model' => config('services.openai.embedding_model'),
                    'input' => $text,
                ])
                ->throw()
                ->json();

            return $response['data'][0]['embedding'] ?? null;
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('OpenAI embedding request failed.', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function summarize(string $title, string $content): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $response = $this->client()
                ->post('/responses', [
                    'model' => config('services.openai.chat_model'),
                    'instructions' => 'You summarize user notes for a notes management app. Return a concise, useful summary in 2 short sentences. Do not add details that are not present in the note.',
                    'input' => "Title: {$title}\n\nNote:\n{$content}",
                    'max_output_tokens' => 160,
                ])
                ->throw()
                ->json();

            return $this->extractText($response);
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('OpenAI summary request failed.', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl('https://api.openai.com/v1')
            ->withToken(config('services.openai.key'))
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->retry(2, 250);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function extractText(array $response): ?string
    {
        if (filled($response['output_text'] ?? null)) {
            return trim($response['output_text']);
        }

        foreach (($response['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $content) {
                $text = $content['text'] ?? null;

                if (filled($text)) {
                    return trim(Str::limit($text, 500, ''));
                }
            }
        }

        return null;
    }
}
