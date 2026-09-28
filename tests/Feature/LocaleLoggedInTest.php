<?php

namespace Tests\Feature;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsProjectData;
use Tests\TestCase;

class LocaleLoggedInTest extends TestCase
{
    use RefreshDatabase, BuildsProjectData;

    public function test_switching_locale_saves_it_on_the_user_and_session(): void
    {
        $admin = $this->makeUser(Role::Admin);

        $this->actingAs($admin)
            ->from(route('settings.index'))
            ->post(route('locale.update'), ['locale' => 'en'])
            ->assertRedirect(route('settings.index'));

        $this->assertSame('en', $admin->fresh()->locale);
        $this->assertSame('en', session('locale'));
    }

    public function test_saved_locale_persists_on_the_next_request(): void
    {
        $admin = $this->makeUser(Role::Admin);

        $this->actingAs($admin)->post(route('locale.update'), ['locale' => 'en']);

        $this->actingAs($admin->fresh())->get(route('settings.index'))
            ->assertOk()
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertDontSee('bootstrap.rtl.min.css', false);
    }

    public function test_saved_user_locale_beats_the_session_value(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $admin->update(['locale' => 'ar']);

        $this->withSession(['locale' => 'en'])
            ->actingAs($admin->fresh())
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false);
    }

    public function test_user_without_a_saved_locale_falls_back_to_the_session(): void
    {
        $admin = $this->makeUser(Role::Admin);
        $this->assertNull($admin->fresh()->locale);

        $this->withSession(['locale' => 'en'])
            ->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('lang="en" dir="ltr"', false);
    }
}