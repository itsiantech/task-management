<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientImportPhoneOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_phone_only_csv_import_creates_fallback_client_records(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $csv = "phone\n+1234567890\n";

        $response = $this->actingAs($admin)->post('/clients/import', [
            'csv_file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('clients.csv', $csv),
        ]);

        $response->assertRedirect('/clients');
        $this->assertSame(1, Client::count());
        $this->assertSame('+1234567890', Client::first()->phone);
        $this->assertSame('Client +1234567890', Client::first()->name);
    }
}
