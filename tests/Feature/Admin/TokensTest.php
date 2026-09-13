<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\MicropubToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TokensTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function index_requires_authentication(): void
    {
        $response = $this->get('/admin/tokens');
        $response->assertRedirect();
    }

    #[Test]
    public function index_lists_issued_tokens(): void
    {
        $user = User::factory()->make();
        $token = MicropubToken::create([
            'token_hash' => hash('sha256', 'a-token'),
            'client_id' => 'https://quill.p3k.io',
            'me' => 'https://jonnybarnes.uk',
            'scope' => 'create update',
        ]);

        $response = $this->actingAs($user)->get('/admin/tokens');
        $response->assertOk();
        $response->assertSeeText($token->client_id);
    }

    #[Test]
    public function create_requires_authentication(): void
    {
        $response = $this->get('/admin/tokens/create');
        $response->assertRedirect();
    }

    #[Test]
    public function create_shows_form(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->get('/admin/tokens/create');

        $response->assertOk();
        $response->assertSee('name="client_id"', false);
    }

    #[Test]
    public function store_requires_authentication(): void
    {
        $response = $this->post('/admin/tokens', [
            'client_id' => 'https://ia.net/writer',
            'scope' => ['create'],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('micropub_tokens', 0);
    }

    #[Test]
    public function store_creates_a_new_token_and_redirects_with_it_flashed(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->post('/admin/tokens', [
            'client_id' => 'https://ia.net/writer',
            'scope' => ['create', 'update'],
        ]);

        $response->assertRedirect('/admin/tokens');
        $response->assertSessionHas('new_token');

        $this->assertDatabaseHas('micropub_tokens', [
            'client_id' => 'https://ia.net/writer',
            'scope' => 'create update',
            'me' => config('app.url'),
        ]);

        $token = $response->getSession()->get('new_token');
        $this->assertNotNull(MicropubToken::findActive($token));
    }

    #[Test]
    public function store_requires_at_least_one_scope(): void
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)->post('/admin/tokens', [
            'client_id' => 'https://ia.net/writer',
            'scope' => [],
        ]);

        $response->assertSessionHasErrors('scope');
        $this->assertDatabaseCount('micropub_tokens', 0);
    }

    #[Test]
    public function revoke_requires_authentication(): void
    {
        $token = MicropubToken::create([
            'token_hash' => hash('sha256', 'a-token'),
            'client_id' => 'https://quill.p3k.io',
            'me' => 'https://jonnybarnes.uk',
            'scope' => 'create',
        ]);

        $response = $this->put("/admin/tokens/{$token->id}/revoke");
        $response->assertRedirect();

        $this->assertFalse($token->fresh()->isRevoked);
    }

    #[Test]
    public function revoke_marks_the_token_as_revoked(): void
    {
        $user = User::factory()->make();
        $token = MicropubToken::create([
            'token_hash' => hash('sha256', 'a-token'),
            'client_id' => 'https://quill.p3k.io',
            'me' => 'https://jonnybarnes.uk',
            'scope' => 'create',
        ]);

        $this->actingAs($user)->put("/admin/tokens/{$token->id}/revoke");

        $this->assertTrue($token->fresh()->isRevoked);
    }

    #[Test]
    public function revoke_redirects_to_index(): void
    {
        $user = User::factory()->make();
        $token = MicropubToken::create([
            'token_hash' => hash('sha256', 'a-token'),
            'client_id' => 'https://quill.p3k.io',
            'me' => 'https://jonnybarnes.uk',
            'scope' => 'create',
        ]);

        $response = $this->actingAs($user)->put("/admin/tokens/{$token->id}/revoke");

        $response->assertRedirect('/admin/tokens');
    }
}
