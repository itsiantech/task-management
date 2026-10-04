<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaskDashboardWithoutPivotTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_index_loads_when_task_user_table_is_missing(): void
    {
        if (Schema::hasTable('task_user')) {
            Schema::drop('task_user');
        }

        $user = User::factory()->create([
            'role' => 'member',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($user)->get('/tasks');

        $response->assertOk();
    }
}
