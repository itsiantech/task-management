<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogle(string $id, string $email, bool $verified = true): void
    {
        $gUser = (new SocialiteUser)->setRaw(['email_verified' => $verified])->map([
            'id' => $id, 'name' => 'Gina Google', 'email' => $email, 'avatar' => 'https://example.com/a.png',
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($gUser);

        $factory = Mockery::mock(SocialiteFactory::class);
        $factory->shouldReceive('driver')->with('google')->andReturn($provider);
        $this->app->instance(SocialiteFactory::class, $factory);
    }

    public function test_login_page_shows_google_button(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in with Google')->assertSee(route('auth.google'), false);
    }

    public function test_redirect_without_credentials_shows_friendly_error(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get('/auth/google')->assertRedirect(route('login'))->assertSessionHasErrors('email');
    }

    public function test_redirect_goes_to_google_when_configured(): void
    {
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);

        $this->get('/auth/google')->assertRedirectContains('accounts.google.com');
    }

    public function test_existing_approved_user_is_linked_by_email_and_logged_in(): void
    {
        $user = User::factory()->create(['email' => 'gina@example.com', 'role' => 'member', 'is_approved' => true]);
        $this->fakeGoogle('g-123', 'Gina@Example.com');

        $this->get('/auth/google/callback')->assertRedirect(route('tasks.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-123', $user->fresh()->google_id);
        $this->assertSame(1, User::count());
    }

    public function test_known_google_id_logs_in(): void
    {
        $user = User::factory()->create(['google_id' => 'g-9', 'email' => 'old@example.com', 'is_approved' => true]);
        $this->fakeGoogle('g-9', 'new@example.com');

        $this->get('/auth/google/callback')->assertRedirect(route('tasks.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_new_google_user_is_created_pending_approval_and_not_logged_in(): void
    {
        $this->fakeGoogle('g-777', 'newbie@example.com');

        $this->get('/auth/google/callback')->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
        $created = User::where('email', 'newbie@example.com')->first();
        $this->assertNotNull($created);
        $this->assertSame('g-777', $created->google_id);
        $this->assertSame('member', $created->role);
        $this->assertFalse($created->is_approved);
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'victim@example.com', 'is_approved' => true]);
        $this->fakeGoogle('g-evil', 'victim@example.com', false);

        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNull(User::where('email', 'victim@example.com')->value('google_id'));
    }
}
