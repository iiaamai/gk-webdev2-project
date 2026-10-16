<?php

namespace Tests\Feature;

use Tests\TestCase;

class MarketingHomeTest extends TestCase
{
    public function test_home_renders_marketing_page_with_sign_in_and_sign_up(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('GK Logistics')
            ->assertSee('Connecting the world through logistics')
            ->assertSee('Sign in')
            ->assertSee('Sign up')
            ->assertSee(route('login'), false)
            ->assertSee(route('register.customer'), false)
            ->assertSee(route('register.driver'), false);
    }
}
