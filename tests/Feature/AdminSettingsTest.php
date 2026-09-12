<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Currency;
use App\Models\GiftPrice;
use App\Models\PlatformAvailableGift;
use App\Models\Setting;
use App\Models\User;
use App\Support\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Credentials and currencies managed from the panel.
 *
 * Two things must hold no matter what: a live gateway key is never rendered
 * back to a browser or wiped by somebody opening the page and pressing save,
 * and a currency in use is never deleted out from under the wallets and gift
 * prices that reference it.
 */
class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = ['settings.manage']): Admin
    {
        $admin = Admin::create([
            'name'     => 'Ops',
            'email'    => 'ops@celebratemi.com',
            'password' => Hash::make('correct-horse-battery-1'),
            'is_super' => false,
            'status'   => 'active',
        ]);

        $admin->syncPermissions($permissions);

        return $admin->fresh('permissions');
    }

    private function repo(): SettingsRepository
    {
        return app(SettingsRepository::class);
    }

    /* ── Access ─────────────────────────────────────────────────────── */

    public function test_settings_need_their_own_permission(): void
    {
        $admin = $this->admin(['payments.view', 'users.view']);

        $this->actingAs($admin, 'admin')->get(route('admin.settings', 'payments'))->assertForbidden();
        $this->actingAs($admin, 'admin')->get(route('admin.currencies'))->assertForbidden();
    }

    /* ── Credentials ────────────────────────────────────────────────── */

    public function test_a_saved_key_overrides_the_env_value(): void
    {
        config(['services.paystack.secret' => 'sk_from_env']);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.update', 'payments'), [
                'settings' => ['paystack_secret' => 'sk_live_from_the_panel'],
            ])
            ->assertRedirect();

        // The overlay is what the provider applies at boot.
        $this->repo()->flush();
        $this->assertSame('sk_live_from_the_panel', $this->repo()->overlay()['services.paystack.secret']);
    }

    public function test_a_secret_is_encrypted_at_rest(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.update', 'payments'), [
                'settings' => ['paystack_secret' => 'sk_live_secret_value'],
            ]);

        $row = Setting::where('key', 'paystack_secret')->firstOrFail();

        $this->assertTrue($row->is_encrypted);
        $this->assertStringNotContainsString('sk_live_secret_value', $row->value);
        $this->assertSame('sk_live_secret_value', $row->plainValue());
    }

    public function test_a_secret_is_never_rendered_back_to_the_browser(): void
    {
        $this->repo()->put('payments', ['paystack_secret' => 'sk_live_do_not_leak']);

        $html = $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.settings', 'payments'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('sk_live_do_not_leak', $html);
        $this->assertStringContainsString('sk_l', $html);   // the masked preview
    }

    public function test_saving_the_form_without_retyping_a_secret_keeps_it(): void
    {
        // The trap this avoids: opening the page, changing the public key, and
        // silently blanking the live secret because its box rendered empty.
        $this->repo()->put('payments', ['paystack_secret' => 'sk_live_keep_me']);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.update', 'payments'), [
                'settings' => ['paystack_secret' => '', 'paystack_public' => 'pk_live_new'],
            ]);

        $this->assertSame('sk_live_keep_me', $this->repo()->describe('payments', 'paystack_secret')['value']);
        $this->assertSame('pk_live_new', $this->repo()->describe('payments', 'paystack_public')['value']);
    }

    public function test_a_secret_is_cleared_only_when_that_is_asked_for(): void
    {
        $this->repo()->put('payments', ['paystack_secret' => 'sk_live_remove_me']);

        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.update', 'payments'), [
                'settings' => ['paystack_secret' => ''],
                'clear'    => ['paystack_secret' => '1'],
            ]);

        $this->assertDatabaseMissing('settings', ['group' => 'payments', 'key' => 'paystack_secret']);
    }

    public function test_the_audit_log_records_the_change_but_never_the_value(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.update', 'payments'), [
                'settings' => ['paystack_secret' => 'sk_live_secret_value'],
            ]);

        $entry = \App\Models\AdminAuditLog::where('action', 'admin.settings.updated')->firstOrFail();

        $this->assertStringContainsString('paystack_secret', $entry->description);
        $this->assertStringNotContainsString('sk_live_secret_value', $entry->description);
    }

    public function test_a_key_outside_the_catalogue_is_ignored(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->put(route('admin.settings.update', 'payments'), [
                'settings' => ['app_key' => 'nice try'],
            ]);

        $this->assertDatabaseMissing('settings', ['key' => 'app_key']);
        $this->assertArrayNotHasKey('app.key', $this->repo()->overlay());
    }

    /* ── Currencies ─────────────────────────────────────────────────── */

    public function test_adding_a_currency_reaches_the_config_gifts_and_signup(): void
    {
        $admin = $this->admin(['settings.manage', 'gifts.manage']);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.currencies.store'), [
                'code'          => 'GHS',
                'name'          => 'Ghanaian Cedi',
                'symbol'        => '₵',
                'decimals'      => 2,
                'fallback_rate' => 15.5,
                'sort_order'    => 30,
                'countries'     => 'gh, ghana',
                'is_active'     => 1,
            ])
            ->assertRedirect();

        $overlay = $this->repo()->overlay();

        // The catalogue the whole app reads…
        $this->assertArrayHasKey('GHS', $overlay['currency.currencies']);
        // …the signup lookup…
        $this->assertSame('GHS', $overlay['currency.country_map']['gh']);
        $this->assertSame('GHS', $overlay['currency.country_map']['ghana']);
        // …and the offline conversion table.
        $this->assertSame(15.5, $overlay['currency.fallback_rates']['GHS']);

        // And with it, a price field on every gift.
        config(['currency.currencies' => $overlay['currency.currencies']]);
        $this->actingAs($admin, 'admin')
            ->get(route('admin.gifts.create'))
            ->assertOk()
            ->assertSee('name="prices[GHS]"', false);
    }

    public function test_the_base_currency_cannot_be_switched_off_or_removed(): void
    {
        $base  = Currency::where('code', strtoupper(config('currency.base')))->firstOrFail();
        $admin = $this->admin();

        // No is_active in the payload — an unticked box — which for the base
        // currency has to be refused rather than obeyed.
        $this->actingAs($admin, 'admin')
            ->put(route('admin.currencies.update', $base), [
                'code' => $base->code, 'name' => $base->name, 'symbol' => $base->symbol,
                'decimals' => 2, 'fallback_rate' => 1, 'sort_order' => 10,
            ])
            ->assertSessionHas('error');

        $this->assertTrue($base->fresh()->is_active);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.currencies.destroy', $base))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('currencies', ['id' => $base->id]);
    }

    public function test_a_currency_in_use_is_deactivated_rather_than_deleted(): void
    {
        $currency = Currency::create([
            'code' => 'KES', 'name' => 'Kenyan Shilling', 'symbol' => 'KSh',
            'decimals' => 2, 'fallback_rate' => 130, 'sort_order' => 40, 'countries' => ['ke'],
        ]);

        $user = User::factory()->create();
        $user->forceFill(['currency' => 'KES'])->save();

        $gift = PlatformAvailableGift::create([
            'gift_name' => 'Kenyan Gift', 'gift_icon' => 'mdi-gift-outline',
            'gift_price' => 10, 'category' => 'small', 'status' => 'active',
        ]);
        GiftPrice::create(['platform_gift_id' => $gift->id, 'currency' => 'KES', 'amount' => 1300]);

        $this->actingAs($this->admin(), 'admin')
            ->delete(route('admin.currencies.destroy', $currency))
            ->assertSessionHas('success');

        // The row survives: a wallet balance and a gift price both reference
        // the code, and deleting it would leave them unreadable.
        $this->assertDatabaseHas('currencies', ['id' => $currency->id, 'is_active' => false]);
        $this->assertDatabaseHas('gift_prices', ['currency' => 'KES']);
        $this->assertSame('KES', $user->fresh()->currency);
    }

    public function test_an_unused_currency_can_be_deleted(): void
    {
        $currency = Currency::create([
            'code' => 'ZAR', 'name' => 'South African Rand', 'symbol' => 'R',
            'decimals' => 2, 'fallback_rate' => 18, 'sort_order' => 50,
        ]);

        $this->actingAs($this->admin(), 'admin')
            ->delete(route('admin.currencies.destroy', $currency));

        $this->assertDatabaseMissing('currencies', ['id' => $currency->id]);
    }

    public function test_an_inactive_currency_leaves_the_config(): void
    {
        $currency = Currency::create([
            'code' => 'GHS', 'name' => 'Ghanaian Cedi', 'symbol' => '₵',
            'decimals' => 2, 'fallback_rate' => 15.5, 'sort_order' => 30, 'countries' => ['gh'],
        ]);

        $this->repo()->flush();
        $this->assertArrayHasKey('GHS', $this->repo()->overlay()['currency.currencies']);

        $currency->update(['is_active' => false]);
        $this->repo()->flush();

        $this->assertArrayNotHasKey('GHS', $this->repo()->overlay()['currency.currencies']);
        $this->assertArrayNotHasKey('gh', $this->repo()->overlay()['currency.country_map']);
    }
}
