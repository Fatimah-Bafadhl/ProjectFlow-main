<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleTest extends TestCase
{
    public function test_guest_defaults_to_arabic_rtl(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('lang="ar" dir="rtl"', false)
            ->assertSee('bootstrap.rtl.min.css', false);
    }

    public function test_guest_can_switch_to_english_and_it_persists(): void
    {
        $this->from('/login')->post('/locale', ['locale' => 'en'])
            ->assertRedirect('/login');

        $this->get('/login')
            ->assertOk()
            ->assertSee('lang="en" dir="ltr"', false)
            ->assertDontSee('bootstrap.rtl.min.css', false);
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->from('/login')->post('/locale', ['locale' => 'fr'])
            ->assertSessionHasErrors('locale');

        $this->get('/login')->assertSee('lang="ar" dir="rtl"', false);
    }
}