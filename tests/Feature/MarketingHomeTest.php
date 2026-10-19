<?php

namespace Tests\Feature;

use App\Models\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_renders_hero_how_it_works_and_benefits(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('GK Logistics')
            ->assertSee('Connecting the world through logistics')
            ->assertSee('How it works')
            ->assertSee('Why GK')
            ->assertSee('© '.now('Asia/Manila')->year.' GK Trucking Services. All rights reserved.')
            ->assertDontSee('Design by', false)
            ->assertDontSee('Ready to book a truck?')
            ->assertSee('Sign in')
            ->assertSee('Sign up')
            ->assertSee(route('login'), false)
            ->assertSee(route('register.customer'), false)
            ->assertSee(route('marketing.how'), false)
            ->assertSee(route('marketing.pricing'), false)
            ->assertSee(route('marketing.faq'), false)
            ->assertSee(asset('images/gk-logo-1.png'), false)
            ->assertSee(asset('images/gk-brand.png'), false)
            ->assertSee(asset('images/gk-fleet.jpg'), false)
            ->assertDontSee(asset('images/gk-truck.png'), false);
    }

    public function test_home_does_not_expose_system_roles_or_driver_signup(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Roles')
            ->assertDontSee('Built for every role')
            ->assertDontSee('System admin')
            ->assertDontSee('Register as a driver')
            ->assertDontSee('Driver signup')
            ->assertDontSee(route('register.driver'), false);
    }

    public function test_how_it_works_page_renders_the_same_customer_flow(): void
    {
        $this->get(route('marketing.how'))
            ->assertOk()
            ->assertSee('How it works')
            ->assertSee('Connecting the world through logistics')
            ->assertSee('Why GK')
            ->assertDontSee('Ready to book a truck?')
            ->assertDontSee('Built for every role')
            ->assertDontSee(route('register.driver'), false);
    }

    public function test_pricing_page_renders_live_catalog_fares(): void
    {
        Pricing::factory()->create([
            'vehicle_type' => 'L300 van',
            'amount' => 4500.00,
            'capacity_kg' => 1000,
        ]);

        $this->get(route('marketing.pricing'))
            ->assertOk()
            ->assertSee('Transparent pricing')
            ->assertSee('L300 van')
            ->assertSee('₱4,500.00')
            ->assertSee('1,000 kg capacity')
            ->assertSee(asset('images/gk-truck-l300.png'), false)
            ->assertDontSee('Ready to book a truck?')
            ->assertDontSee('Listed in-app')
            ->assertDontSee('Built for every role')
            ->assertDontSee(route('register.driver'), false);
    }

    public function test_pricing_page_omits_archived_fares_and_shows_empty_copy(): void
    {
        $archived = Pricing::factory()->create([
            'vehicle_type' => 'Reefer / specialized',
            'amount' => 18500.00,
            'capacity_kg' => 5000,
        ]);
        $archived->archive();

        $this->get(route('marketing.pricing'))
            ->assertOk()
            ->assertDontSee('Reefer / specialized')
            ->assertSee('Fares will appear here when the pricing list is published');
    }

    public function test_pricing_page_omits_image_for_an_unmapped_vehicle_type(): void
    {
        Pricing::factory()->create([
            'vehicle_type' => 'Closed van',
            'amount' => 6000.00,
            'capacity_kg' => 1500,
        ]);

        $this->get(route('marketing.pricing'))
            ->assertOk()
            ->assertSee('Closed van')
            ->assertSee('₱6,000.00')
            ->assertDontSee(asset('images/gk-truck-l300.png'), false)
            ->assertDontSee(asset('images/gk-truck-4-wheeler.png'), false)
            ->assertDontSee(asset('images/gk-truck-6-wheeler.png'), false)
            ->assertDontSee(asset('images/gk-truck-reefer.png'), false);
    }

    public function test_faq_page_renders_customer_questions_without_driver_signup(): void
    {
        $this->get(route('marketing.faq'))
            ->assertOk()
            ->assertSee('Do I need an account to book?')
            ->assertSee('How do I book a trip?')
            ->assertSee('How do I track my booking?')
            ->assertSee('Can I cancel a booking?')
            ->assertSee('What documents will I see?')
            ->assertSee('How do I pay?')
            ->assertSee('How is the fare calculated?')
            ->assertSee('How do I update my account details?')
            ->assertDontSee('Ready to book a truck?')
            ->assertDontSee('Can drivers register')
            ->assertDontSee('Built for every role')
            ->assertDontSee(route('register.driver'), false);
    }
}
