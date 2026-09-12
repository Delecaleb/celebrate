<?php

namespace Tests\Feature;

use App\Models\Celebration;
use App\Models\User;
use App\Models\Wish;
use App\Models\WishContribution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Paying for a registry item without leaving the celebration page.
 *
 * The registry is where most of the money comes in, so the guard that matters
 * is the same one as for gifts: a contribution counts towards the goal when,
 * and only when, Paystack says the money arrived.
 */
class InlineContributionTest extends TestCase
{
    use RefreshDatabase;

    private function wish(): Wish
    {
        $celebration = Celebration::create([
            'uuid'             => (string) Str::uuid(),
            'user_id'          => User::factory()->create()->id,
            'title'            => "Yemi's Birthday",
            'slug'             => 'yemi-inline-' . Str::random(6),
            'celebration_type' => 'birthday',
            'celebrant_name'   => 'Yemi',
            'status'           => 'published',
            'is_public'        => true,
        ]);

        return Wish::create([
            'celebration_id' => $celebration->id,
            'name'           => 'Nike Air Max',
            'wish_type'      => 'cash',
            'target_amount'  => 150.00,
            'amount_base'    => 150.00,
            'base_currency'  => 'USD',
            'current_amount' => 0,
            'currency'       => 'USD',
            'status'         => 'active',
        ]);
    }

    private function pendingContribution(Wish $wish, string $reference): WishContribution
    {
        return WishContribution::create([
            'celebration_id'    => $wish->celebration_id,
            'wish_id'           => $wish->id,
            'contributor_name'  => 'Ada Guest',
            'contributor_email' => 'ada@example.com',
            'amount'            => 25,
            'currency'          => 'USD',
            'original_amount'   => 25,
            'contribution_type' => 'cash',
            'payment_reference' => $reference,
            'payment_status'    => 'pending',
            'is_anonymous'      => false,
        ]);
    }

    private function fakeVerify(string $status): void
    {
        config(['services.paystack.secret' => 'sk_test_stub']);

        Http::fake([
            'api.paystack.co/transaction/verify/*' => Http::response([
                'status' => true,
                'data'   => ['status' => $status, 'amount' => 2500, 'currency' => 'USD'],
            ], 200),
        ]);
    }

    public function test_a_verified_contribution_counts_towards_the_goal(): void
    {
        $wish      = $this->wish();
        $reference = 'wish-pay-' . Str::uuid();
        $this->pendingContribution($wish, $reference);

        $this->fakeVerify('success');

        $this->postJson(route('wish.contribute.confirm'), ['reference' => $reference])
            ->assertOk()
            ->assertJson(['success' => true]);

        $wish->refresh();
        $this->assertEquals(25.0, (float) $wish->current_amount);
        $this->assertSame(1, (int) $wish->contribution_count);
    }

    public function test_a_contribution_paystack_has_not_seen_counts_for_nothing(): void
    {
        $wish      = $this->wish();
        $reference = 'wish-pay-' . Str::uuid();
        $contribution = $this->pendingContribution($wish, $reference);

        $this->fakeVerify('abandoned');

        $this->postJson(route('wish.contribute.confirm'), ['reference' => $reference])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame('pending', $contribution->fresh()->payment_status);
        $this->assertEquals(0.0, (float) $wish->fresh()->current_amount);
    }

    public function test_an_unknown_reference_is_refused(): void
    {
        $this->fakeVerify('success');

        $this->postJson(route('wish.contribute.confirm'), ['reference' => 'wish-pay-never-existed'])
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_confirming_twice_credits_the_goal_once(): void
    {
        // This endpoint and the webhook race on every real payment.
        $wish      = $this->wish();
        $reference = 'wish-pay-' . Str::uuid();
        $this->pendingContribution($wish, $reference);

        $this->fakeVerify('success');

        $this->postJson(route('wish.contribute.confirm'), ['reference' => $reference])->assertOk();
        $this->postJson(route('wish.contribute.confirm'), ['reference' => $reference])
            ->assertOk()
            ->assertJson(['already' => true]);

        $this->assertEquals(25.0, (float) $wish->fresh()->current_amount);
        $this->assertSame(1, (int) $wish->fresh()->contribution_count);
    }

    public function test_a_reference_is_required(): void
    {
        $this->postJson(route('wish.contribute.confirm'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reference');
    }
}
