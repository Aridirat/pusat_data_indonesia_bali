<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_successful_response()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('data.index'));

        $response->assertOk();
    }

    public function test_unauthenticated_users_are_redirected_to_login()
    {
        $response = $this->get(route('data.index'));
        $response->assertRedirect('/login');
    }
}