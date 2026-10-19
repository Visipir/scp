<?php

namespace Tests\Feature\Api\V1;

use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Support\ScpTestHelpers;
use Tests\TestCase;

class ChatApiTest extends TestCase
{
    use RefreshDatabase;
    use ScpTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        RateLimiter::clear('api-chat');
    }

    public function test_chat_requires_authentication(): void
    {
        $this->postJson('/api/v1/chat/message', [
            'message' => 'Halo',
        ])->assertStatus(401);
    }

    public function test_chat_message_validation(): void
    {
        $user = $this->makeUser(2);

        // Empty message
        $this->apiAs($user)->postJson('/api/v1/chat/message', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);

        // Message too long (> 1000 chars)
        $this->apiAs($user)->postJson('/api/v1/chat/message', [
            'message' => str_repeat('a', 1001),
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['message']);

        // Invalid history format
        $this->apiAs($user)->postJson('/api/v1/chat/message', [
            'message' => 'Halo',
            'history' => [
                ['role' => 'invalid_role', 'content' => 'Test'],
            ],
        ])->assertStatus(422)
          ->assertJsonValidationErrors(['history.0.role']);
    }

    public function test_chat_happy_path_with_mocked_gemini(): void
    {
        $user = $this->makeUser(2);
        SystemConfiguration::create([
            'gemini_api_key'    => 'AIzaSyFakeSecretKeyForTesting',
            'gemini_chat_model' => 'gemini-3.5-flash-lite',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Halo Guru! Ada yang bisa saya bantu terkait jadwal atau pengisian jurnal?'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->apiAs($user)->postJson('/api/v1/chat/message', [
            'message' => 'Bagaimana cara mengisi jurnal?',
            'history' => [
                ['role' => 'user', 'content' => 'Halo'],
                ['role' => 'model', 'content' => 'Halo! Ada yang bisa saya bantu?'],
            ],
        ])->assertOk()
          ->assertJson([
              'data' => [
                  'reply' => 'Halo Guru! Ada yang bisa saya bantu terkait jadwal atau pengisian jurnal?',
              ],
          ]);

        Http::assertSent(function ($request) {
            // Verify key is passed in header and not in query string URL
            return $request->hasHeader('x-goog-api-key', 'AIzaSyFakeSecretKeyForTesting')
                && !str_contains($request->url(), 'key=');
        });
    }

    public function test_chat_provider_failure_returns_json_error(): void
    {
        $user = $this->makeUser(2);
        SystemConfiguration::create([
            'gemini_api_key'    => 'AIzaSyFakeSecretKeyForTesting',
            'gemini_chat_model' => 'gemini-3.5-flash-lite',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'code'    => 500,
                    'message' => 'Internal server error from AI backend',
                ],
            ], 500),
        ]);

        $response = $this->apiAs($user)->postJson('/api/v1/chat/message', [
            'message' => 'Pertanyaan',
        ])->assertStatus(502)
          ->assertJsonStructure(['message', 'errors']);

        // Verify API key is NOT leaked in message
        $this->assertStringNotContainsString('AIzaSyFakeSecretKeyForTesting', $response->json('message'));
    }

    public function test_chat_rate_limiting(): void
    {
        $user = $this->makeUser(2);
        SystemConfiguration::create([
            'gemini_api_key' => 'AIzaSyFakeKey',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Jawaban'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        // Hit 10 times (limit is 10/min)
        for ($i = 0; $i < 10; $i++) {
            $this->apiAs($user)->postJson('/api/v1/chat/message', ['message' => "Pesan ke-$i"])->assertOk();
        }

        // 11th time should be 429
        $response = $this->apiAs($user)->postJson('/api/v1/chat/message', ['message' => 'Pesan ke-11'])
            ->assertStatus(429);

        $this->assertStringContainsString('Terlalu banyak permintaan chat', $response->json('message'));
    }
}
