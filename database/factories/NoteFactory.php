<?php

namespace Database\Factories;

use App\Services\TextEmbeddingService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(4);
        $content = fake()->paragraphs(3, true);

        return [
            'title' => $title,
            'content' => $content,
            'summary' => null,
            'embedding' => app(TextEmbeddingService::class)->embed($title.' '.$content),
        ];
    }
}
