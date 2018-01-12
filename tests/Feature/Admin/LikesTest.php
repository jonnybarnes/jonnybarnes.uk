<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Like;
use App\Jobs\ProcessLike;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LikesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_index_page()
    {
        $response = $this->withSession(['loggedin' => true])
                         ->get('/admin/likes');
        $response->assertSeeText('Likes');
    }

    public function test_create_page()
    {
        $response = $this->withSession(['loggedin' => true])
                         ->get('/admin/likes/create');
        $response->assertSeeText('New Like');
    }

    public function test_create_new_like()
    {
        Queue::fake();
        $this->withSession(['loggedin' => true])
             ->post('/admin/likes', [
                 'like_url' => 'https://example.com'
             ]);
        $this->assertDatabaseHas('likes', [
            'url' => 'https://example.com'
        ]);
        Queue::assertPushed(ProcessLike::class);
    }

    public function test_see_edit_form()
    {
        $response = $this->withSession(['loggedin' => true])
                         ->get('/admin/likes/1/edit');
        $response->assertSee('Edit Like');
    }

    public function test_edit_like()
    {
        Queue::fake();
        $this->withSession(['loggedin' => true])
             ->post('/admin/likes/1', [
                 '_method' => 'PUT',
                 'like_url' => 'https://example.com',
             ]);
        $this->assertDatabaseHas('likes', [
            'url' => 'https://example.com',
        ]);
        Queue::assertPushed(ProcessLike::class);
    }

    public function test_delete_like()
    {
        $like = Like::find(1);
        $url = $like->url;
        $this->withSession(['loggedin' => true])
             ->post('/admin/likes/1', [
                 '_method' => 'DELETE',
             ]);
        $this->assertDatabaseMissing('likes', [
            'url' => $url,
        ]);
    }
}
