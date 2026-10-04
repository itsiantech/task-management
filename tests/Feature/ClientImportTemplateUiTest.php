<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientImportTemplateUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_instruction_block_is_not_duplicated(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_approved' => true,
        ]);

        $response = $this->actingAs($admin)->get('/clients');

        $response->assertOk();

        $html = $response->getContent();
        $needle = 'Phone-only lists: just a phone column is enough';

        $this->assertSame(1, substr_count($html, $needle));
        $this->assertSame(1, substr_count($html, 'clients_template.csv'));
    }
}
