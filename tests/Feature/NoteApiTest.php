<?php

namespace Tests\Feature;

use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NoteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_notes_can_be_created_listed_updated_and_deleted(): void
    {
        $createResponse = $this->postJson('/api/notes', [
            'title' => 'Sprint planning',
            'content' => 'Capture backend API tasks and frontend polish items.',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.title', 'Sprint planning');

        $noteId = $createResponse->json('data.id');

        $this->getJson('/api/notes?limit=5')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $noteId);

        $this->putJson("/api/notes/{$noteId}", [
            'title' => 'Updated sprint planning',
            'content' => 'Capture backend API tasks, tests, and frontend polish items.',
        ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated sprint planning');

        $this->deleteJson("/api/notes/{$noteId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('notes', ['id' => $noteId]);
    }

    public function test_note_creation_validates_payload(): void
    {
        $this->postJson('/api/notes', [
            'title' => '',
            'content' => 'Tiny',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'content']);
    }

    public function test_semantic_search_returns_ranked_matches(): void
    {
        Note::factory()->create([
            'title' => 'Machine learning roadmap',
            'content' => 'Embeddings, vector search, and semantic retrieval are planned.',
        ]);
        Note::factory()->create([
            'title' => 'Grocery list',
            'content' => 'Milk, rice, onions, and tea.',
        ]);

        $this->getJson('/api/notes/search?q=vector%20embeddings')
            ->assertOk()
            ->assertJsonPath('data.0.note.title', 'Machine learning roadmap')
            ->assertJsonPath('meta.method', 'local-hashed-embedding');
    }

    public function test_summary_endpoint_generates_and_persists_summary(): void
    {
        $note = Note::factory()->create([
            'title' => 'Product launch',
            'content' => 'The launch plan covers beta feedback and support readiness. Marketing assets are waiting for approval. The team will review metrics after release.',
            'summary' => null,
        ]);

        $this->postJson("/api/notes/{$note->id}/summary")
            ->assertOk()
            ->assertJsonPath('data.id', $note->id)
            ->assertJsonStructure(['data' => ['summary']]);

        $this->assertNotNull($note->refresh()->summary);
    }

    public function test_summary_endpoint_uses_openai_when_configured(): void
    {
        config([
            'services.openai.key' => 'test-key',
            'services.openai.chat_model' => 'gpt-test',
        ]);

        Http::fake([
            'api.openai.com/v1/responses' => Http::response([
                'output_text' => 'OpenAI generated summary.',
            ]),
        ]);

        $note = Note::factory()->create([
            'title' => 'Customer research',
            'content' => 'Interview notes mention onboarding friction and clearer checklist needs.',
            'summary' => null,
        ]);

        $this->postJson("/api/notes/{$note->id}/summary")
            ->assertOk()
            ->assertJsonPath('data.summary', 'OpenAI generated summary.');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.openai.com/v1/responses'
            && $request['model'] === 'gpt-test'
            && str_contains($request['input'], 'Customer research'));
    }

    public function test_note_creation_uses_openai_embeddings_when_configured(): void
    {
        config([
            'services.openai.key' => 'test-key',
            'services.openai.embedding_model' => 'embedding-test',
        ]);

        Http::fake([
            'api.openai.com/v1/embeddings' => Http::response([
                'data' => [
                    ['embedding' => [0.1, 0.2, 0.3]],
                ],
            ]),
        ]);

        $this->postJson('/api/notes', [
            'title' => 'Vector note',
            'content' => 'This content should be embedded with OpenAI.',
        ])->assertCreated();

        $this->assertDatabaseHas('notes', [
            'title' => 'Vector note',
        ]);

        $this->assertSame([0.1, 0.2, 0.3], Note::query()->where('title', 'Vector note')->first()->embedding);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.openai.com/v1/embeddings'
            && $request['model'] === 'embedding-test');
    }
}
