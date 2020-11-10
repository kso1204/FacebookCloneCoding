<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostToTimeLineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_a_user_can_post_a_text_post()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $response = $this->post('/api/posts', [
               'body' => 'Testings Body',
        ]);

        $post = Post::first();
        //4
        $this->assertCount(1, Post::all());


        //3
        $this->assertEquals($user->id, $post->user_id);
        $this->assertEquals('Testings Body', $post->body);

        //2
        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'type' => 'posts',
                    'post_id' => $post->id,
                    'attributes' => [
                        'posted_by' => [
                            'data' => [
                                'attributes' => [
                                    'name' => $user->name,
                                ]
                            ]
                        ],
                        'body' => 'Testings Body',
                    ]
                ],
                'links' => [
                    'self' => url('/posts/'.$post->id),
                ]
            ]);

        
    }

    public function test_a_user_can_post_a_text_post_with_an_image()
    {
        $this->withoutExceptionHandling();
        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $file= UploadedFile::fake()->image('user-post.png');

        $response = $this->post('/api/posts', [
               'body' => 'Testings Body',
               'image' => $file,
               'width' => 100,
               'height' => 100,
        ]);

        Storage::disk('public')->assertExists('post-images/'.$file->hashName());

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'body' => 'Testings Body',
                        'image' => url('post->images/'.$file->hashName()),
                    ]
                ]
            ]);

        
    }
}
