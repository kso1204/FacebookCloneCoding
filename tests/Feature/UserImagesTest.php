<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\UserImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserImagesTest extends TestCase
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


     public function test_images_can_be_uploaded()
     {
         $this->withoutExceptionHandling();
         $user = User::factory()->create();
         
         $this->actingAs($user, 'api');

         $file = UploadedFile::fake()->image('user-image.png');

         $response = $this->post('/api/user-images', [
            'image' => $file,
            'width' => 850,
            'height' => 300,
            'location' => 'cover',
         ])->assertStatus(201);

        Storage::disk('public')->assertExists('user-images/'.$file->hashName());    
        $userImage = UserImage::first();
        $this->assertEquals('/storage/user-images/'.$file->hashName(), $userImage->path);
        $this->assertEquals('850', $userImage->width);
        $this->assertEquals('300', $userImage->height);
        $this->assertEquals('cover', $userImage->location);
        $this->assertEquals($user->id, $userImage->user_id);

        $response->assertJson([
            'data' => [
                'type' => 'user-images',
                'user_image_id' => $userImage->id,
                'attributes' => [
                    'path' => url($userImage->path),
                    'width' => $userImage->width,
                    'height' => $userImage->height,
                    'location' => $userImage->location,
                ]
                ],
            'links' => [
                'self' => url('/users/'.$user->id)
            ]
        ]);



     }

     
    public function test_users_are_required_with_their_images()
    {
       $user = User::factory()->create();
       
       $this->actingAs($user, 'api');

       $file = UploadedFile::fake()->image('user-image.png');

       $this->post('/api/user-images', [
          'image' => $file,
          'width' => 850,
          'height' => 300,
          'location' => 'cover',
       ])->assertStatus(201);

       
       $this->post('/api/user-images', [
        'image' => $file,
        'width' => 850,
        'height' => 300,
        'location' => 'profile',
     ])->assertStatus(201);

       $response = $this->get('/api/users/'.$user->id);

       $userImage = UserImage::first();

       $response->assertJson([
           'data' => [
               'type' => 'users',
               'user_id' => $user->id,
               'attributes' => [
                   'cover_image' => [
                       'data' => [
                           'type' => 'user-images',
                           'user_image_id' => 1,
                           'attributes' => [],
                        ]
                       ],
                       'profile_image' => [
                           'data' => [
                               'type' => 'user-images',
                               'user_image_id' => 2,
                               'attributes' => [],
                            ]
                        ]
                ]
            ]
       ]);
    }
     
    
}
