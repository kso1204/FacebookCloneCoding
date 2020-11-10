<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use App\Models\Friend;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FriendsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_send_friend_request()
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $anotherUser = User::factory()->create();

        $response = $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);
        
        $friendRequest = Friend::first();

        $this->assertNotNull($friendRequest);

        $this->assertEquals($anotherUser->id, $friendRequest->friend_id);
        $this->assertEquals($user->id, $friendRequest->user_id);

        $response->assertJson([
            'data' => [
                'type' => 'friend-request',
                'friend_request_id' => $friendRequest->id,
                'attributes' => [
                    'confirmed_at' => null,
                ]
                ],
            'links' => [
                'self' => url('/users/'.$anotherUser->id),
            ]
        ]);
    }

    public function test_only_valid_users_can_be_friend_requested()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $anotherUser = User::factory()->create();

        $response = $this->post('/api/friend-request', [
            'friend_id' => 123,
        ])->assertStatus(404);
        
        $friendRequest = Friend::first();

        $this->assertNull($friendRequest);

        $response->assertJson([
            'errors' => [
                'code' => 404,
                'title' => 'User Not Found',
                'detail' => 'Unable to locat the user with the given information',
            ]
        ]);
    }

    public function test_friend_request_can_be_accepted()
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $anotherUser = User::factory()->create();

        $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);

        $response = $this->actingAs($anotherUser, 'api')
            ->post('/api/friend-request-response', [
                'user_id' => $user->id,
                'status' => 1,
            ])->assertStatus(200);
        
        $friendRequest = Friend::first();

        $this->assertNotNull($friendRequest->confirmed_at);
        $this->assertInstanceOf(Carbon::class, $friendRequest->confirmed_at);
        $this->assertEquals(now()->startOfSecond(), $friendRequest->confirmed_at);
        $this->assertEquals(1, $friendRequest->status);

        $response->assertJson([
            'data' => [
                'type' => 'friend-request',
                'friend_request_id' => $friendRequest->id,
                'attributes' => [
                    'confirmed_at' => $friendRequest->confirmed_at->diffForHumans(),
                    'friend_id' => $friendRequest->friend_id,
                    'user_id' => $friendRequest->user_id,
                ]
             ],
            'links' => [
                'self' => url('/users/'.$anotherUser->id),
            ]
        ]);



    }

    public function test_only_valid_friend_requests_can_be_accepted()
    {
        
        $anotherUser = User::factory()->create();

        $response = $this->actingAs($anotherUser, 'api')
        ->post('/api/friend-request-response', [
            'user_id' => 123,
            'status' => 1,
        ])->assertStatus(404);
    
        $friendRequest = Friend::first();

        $this->assertNull($friendRequest);

        $response->assertJson([
            'errors' => [
                'code' => 404,
                'title' => 'Friend Request Not Found',
                'detail' => 'Unable to locat the user with the given information',
            ]
        ]);
    }

    public function test_only_the_recipient_can_accept_a_friend_request()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $anotherUser = User::factory()->create();

        $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);

        
        $thirdUser = User::factory()->create();

        $response = $this->actingAs($thirdUser, 'api')
            ->post('/api/friend-request-response', [
                'user_id' => $user->id,
                'status' => 1,
            ])->assertStatus(404);

        
        $friendRequest = Friend::first();

            
        $this->assertNull($friendRequest->confirmed_at);
        $this->assertNull($friendRequest->status);

        $response->assertJson([
            'errors' => [
                'code' => 404,
                'title' => 'Friend Request Not Found',
                'detail' => 'Unable to locat the user with the given information',
            ]
        ]);
    }

    public function test_a_friend_id_is_required_for_friend_requests()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'api')
                    ->post('/api/friend-request', [
                        'friend_id' => '',
                    ]);

        $responseString = json_decode($response->getContent(), true);
        $this->assertArrayHaskey('friend_id', $responseString['errors']['meta']);
    }

    public function test_a_user_id_and_status_is_required_for_friend_request_responses()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'api')
            ->post('/api/friend-request-response', [
                'user_id' => '',
                'status' => '',
            ])->assertStatus(422);

            $responseString = json_decode($response->getContent(), true);
            $this->assertArrayHaskey('user_id', $responseString['errors']['meta']);
            $this->assertArrayHaskey('status', $responseString['errors']['meta']);
    }

    public function test_a_friendship_is_retrieved_when_fetching_the_profile()
    {
        $user= User::factory()->create();

        $this->actingAs($user, 'api');

        $anotherUser = User::factory()->create();

        $friendRequest = Friend::create([
            'user_id' => $user->id,
            'friend_id' => $anotherUser->id,
            'confirmed_at' => now()->subDay(),
            'status' => 1,
        ]);

        
        $this->get('/api/users/'.$anotherUser->id)
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'friendship' => [
                            'data' => [
                                'friend_request_id' => $friendRequest->id,
                                'attributes' => [
                                    'confirmed_at' => '1 day ago',
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function test_an_inverse_friendship_is_retrieved_when_fetching_the_profile()
    {
        $user= User::factory()->create();

        $this->actingAs($user, 'api');

        $anotherUser = User::factory()->create();

        $friendRequest = Friend::create([
            'friend_id' => $user->id,
            'user_id' => $anotherUser->id,
            'confirmed_at' => now()->subDay(),
            'status' => 1,
        ]);

        
        $this->get('/api/users/'.$anotherUser->id)
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'attributes' => [
                        'friendship' => [
                            'data' => [
                                'friend_request_id' => $friendRequest->id,
                                'attributes' => [
                                    'confirmed_at' => '1 day ago',
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
    }

    
    public function test_friend_request_can_be_ignored()
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $anotherUser = User::factory()->create();

        $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);

        $response = $this->actingAs($anotherUser, 'api')
            ->delete('/api/friend-request-response/delete', [
                'user_id' => $user->id,
                'status' => 1,
            ])->assertStatus(204);
        
        $friendRequest = Friend::first();

        $this->assertNull($friendRequest);

        $response->assertNoContent();




    }

    
    public function test_only_the_recipient_can_ignore_a_friend_request()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $anotherUser = User::factory()->create();

        $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);

        
        $thirdUser = User::factory()->create();

        $response = $this->actingAs($thirdUser, 'api')
            ->delete('/api/friend-request-response/delete', [
                'user_id' => $user->id,
                'status' => 1,
            ])->assertStatus(404);

        
        $friendRequest = Friend::first();

            
        $this->assertNull($friendRequest->confirmed_at);
        $this->assertNull($friendRequest->status);

        $response->assertJson([
            'errors' => [
                'code' => 404,
                'title' => 'Friend Request Not Found',
                'detail' => 'Unable to locat the user with the given information',
            ]
        ]);
    }

    
    public function test_a_user_id_is_required_for_ignoring_a_friend_request_responses()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'api')
            ->delete('/api/friend-request-response/delete', [
                'user_id' => '',
            ])->assertStatus(422);

            $responseString = json_decode($response->getContent(), true);
            $this->assertArrayHaskey('user_id', $responseString['errors']['meta']);
    }
    
    public function test_a_user_can_send_friend_request_only_once()
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();

        $this->actingAs($user, 'api');

        $anotherUser = User::factory()->create();

        $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);

        $this->post('/api/friend-request', [
            'friend_id' => $anotherUser->id,
        ])->assertStatus(200);
        
        $friendRequest = Friend::all();

        $this->assertCount(1, $friendRequest);

    }
}
