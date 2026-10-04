<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentDashboardWithoutTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_pages_load_when_payments_table_is_missing(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::drop('payments');
        }

        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        User::factory()->create([
            'role' => 'member',
            'is_approved' => true,
        ]);

        $this->actingAs($admin)->get('/admin/payments')->assertOk();
        $this->actingAs($admin)->get('/admin/members')->assertOk();
        $this->actingAs($admin)->get('/payments')->assertOk();
    }
}
