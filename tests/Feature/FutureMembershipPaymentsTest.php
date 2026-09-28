<?php

namespace Tests\Feature;

use App\Models\Clanovi;
use App\Models\ClanPaymentCharge;
use App\Models\ClanPaymentProfile;
use App\Models\MembershipPaymentOption;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\PaymentTrackingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class FutureMembershipPaymentsTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_future_unpaid_season_is_only_visible_to_admin_until_it_starts(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 28)->startOfDay());
        [$clan, $admin, $member] = $this->seasonalMember();
        $service = app(PaymentTrackingService::class);

        $summary = $service->memberSummary($clan);
        $future = $summary['adminCharges']->firstWhere('period_key', 'season-oct-2026');

        $this->assertInstanceOf(ClanPaymentCharge::class, $future);
        $this->assertSame(PaymentTrackingService::STATUS_OPEN, $future->status);
        $this->assertFalse($summary['charges']->contains('id', $future->id));
        $this->assertFalse($summary['unpaidCharges']->contains('id', $future->id));
        $this->assertEquals(90.0, $summary['totalOpenAmount']);
        $this->assertEquals(90.0, $service->listStatusForClanIds([$clan->id])[$clan->id]['amount']);

        $report = $this->actingAs($admin)->get(route('admin.placanja.index', [
            'period_preset' => 'all',
            'status' => 'open',
            'target' => 'member',
        ]));
        $report->assertOk();
        $this->assertFalse($report->viewData('reportRows')->contains('id', $future->id));

        $this->actingAs($admin)
            ->get(route('admin.clanovi.prikaz_clana', ['clan' => $clan, 'open_payments' => 1]))
            ->assertOk()
            ->assertSee('Dvoranska sezona 2026/2027')
            ->assertSee('Buduće razdoblje')
            ->assertSee('Podupirući član');

        $this->actingAs($member)
            ->get(route('javno.clanovi.placanja', $clan))
            ->assertOk()
            ->assertDontSee('Dvoranska sezona 2026/2027');
        $this->get(route('javno.clanovi.prikaz_clana', $clan))
            ->assertOk()
            ->assertDontSee('Dvoranska sezona 2026/2027');

        $this->post(route('javno.clanovi.placanja.odabir', [$clan, $future]), [
            'payment_variant' => PaymentTrackingService::VARIANT_SUPPORTING,
        ])->assertNotFound();

        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());
        $current = $service->memberSummary($clan);

        $this->assertTrue($current['charges']->contains('id', $future->id));
        $this->assertTrue($current['currentUnpaidCharges']->contains('id', $future->id));
        $this->actingAs($member)
            ->get(route('javno.clanovi.placanja', $clan))
            ->assertOk()
            ->assertSee('Dvoranska sezona 2026/2027');
    }

    public function test_admin_can_confirm_future_supporting_membership_and_member_sees_prepayment(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 28)->startOfDay());
        [$clan, $admin, $member] = $this->seasonalMember();
        $service = app(PaymentTrackingService::class);
        $summary = $service->memberSummary($clan);
        $outdoor = $summary['adminCharges']->firstWhere('period_key', 'season-apr-2026');
        $future = $summary['adminCharges']->firstWhere('period_key', 'season-oct-2026');

        $this->assertInstanceOf(ClanPaymentCharge::class, $outdoor);
        $this->assertInstanceOf(ClanPaymentCharge::class, $future);

        $this->actingAs($admin)
            ->post(route('admin.clanovi.placanja.status', [$clan, $outdoor]), [
                'is_paid' => 1,
                'paid_at' => '2026-09-28',
                'payment_variant' => PaymentTrackingService::VARIANT_FULL,
            ])->assertRedirect();

        $beforePrepayment = $service->memberSummary($clan);
        $this->assertEquals(0.0, $beforePrepayment['totalOpenAmount']);
        $this->assertFalse($beforePrepayment['hasWarnings']);
        $this->assertSame('paid', $service->listStatusForClanIds([$clan->id])[$clan->id]['state']);

        $this->actingAs($admin)
            ->post(route('admin.clanovi.placanja.status', [$clan, $future]), [
                'is_paid' => 1,
                'paid_at' => '2026-09-28',
                'payment_variant' => PaymentTrackingService::VARIANT_SUPPORTING,
            ])->assertRedirect();

        $future->refresh();
        $this->assertSame(PaymentTrackingService::STATUS_PAID, $future->status);
        $this->assertSame(PaymentTrackingService::VARIANT_SUPPORTING, $future->metadata['payment_variant']);
        $this->assertEquals(30.0, $future->amount);

        $paidSummary = $service->memberSummary($clan);
        $this->assertTrue($paidSummary['charges']->contains('id', $future->id));
        $this->assertEquals(0.0, $paidSummary['totalOpenAmount']);
        $report = $this->actingAs($admin)->get(route('admin.placanja.index', [
            'period_preset' => 'custom',
            'date_from' => '2026-09-28',
            'date_to' => '2026-09-28',
            'status' => 'paid',
            'target' => 'member',
        ]));
        $report->assertOk();
        $this->assertTrue($report->viewData('reportRows')->contains('id', $future->id));

        $this->actingAs($member)
            ->get(route('javno.clanovi.placanja', $clan))
            ->assertOk()
            ->assertSee('Dvoranska sezona 2026/2027')
            ->assertSee('Plaćeno unaprijed')
            ->assertSee('Podupirući član');
        $this->get(route('javno.clanovi.prikaz_clana', $clan))
            ->assertOk()
            ->assertSee('Dvoranska sezona 2026/2027')
            ->assertSee('plaćeno unaprijed');

        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());
        $afterSeasonStart = $service->memberSummary($clan);
        $this->assertTrue($afterSeasonStart['currentCharges']->contains('id', $future->id));
        $this->assertFalse($afterSeasonStart['currentUnpaidCharges']->contains('id', $future->id));
    }

    public function test_admin_can_confirm_future_full_membership(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 28)->startOfDay());
        [$clan, $admin, $member] = $this->seasonalMember();
        $future = app(PaymentTrackingService::class)
            ->memberSummary($clan)['adminCharges']
            ->firstWhere('period_key', 'season-oct-2026');

        $this->assertInstanceOf(ClanPaymentCharge::class, $future);

        $this->actingAs($admin)
            ->post(route('admin.clanovi.placanja.status', [$clan, $future]), [
                'is_paid' => 1,
                'paid_at' => '2026-09-28',
                'payment_variant' => PaymentTrackingService::VARIANT_FULL,
            ])->assertRedirect();

        $future->refresh();
        $this->assertSame(PaymentTrackingService::STATUS_PAID, $future->status);
        $this->assertSame(PaymentTrackingService::VARIANT_FULL, $future->metadata['payment_variant']);
        $this->assertEquals(90.0, $future->amount);

        $this->actingAs($member)
            ->get(route('javno.clanovi.placanja', $clan))
            ->assertOk()
            ->assertSee('Dvoranska sezona 2026/2027')
            ->assertSee('Plaćeno unaprijed');
    }

    private function seasonalMember(): array
    {
        $settings = SiteSetting::query()->firstOrCreate([], [
            'theme_mode_policy' => 'auto',
        ]);
        $settings->payment_tracking_enabled = true;
        $settings->save();

        $suffix = Str::lower(Str::random(12));
        $clan = Clanovi::query()->create([
            'Ime' => 'Test',
            'Prezime' => 'Uplata',
            'datum_rodjenja' => '1990-01-01',
            'spol' => 'M',
            'oib' => (string) random_int(10000000000, 99999999999),
        ]);
        $admin = User::query()->create([
            'name' => 'Test admin',
            'email' => 'payment-admin-'.$suffix.'@example.test',
            'password' => 'test-password',
            'rola' => 1,
        ]);
        $member = User::query()->create([
            'name' => 'Test član',
            'email' => 'payment-member-'.$suffix.'@example.test',
            'password' => 'test-password',
            'rola' => 2,
            'clan_id' => $clan->id,
        ]);
        $option = MembershipPaymentOption::query()->create([
            'key' => 'test-seasonal-'.$suffix,
            'name' => 'Test sezonski model',
            'period_type' => 'seasonal',
            'period_anchor' => 'oct',
            'is_enabled' => true,
            'is_archived' => false,
            'sort_order' => 9999,
        ]);

        ClanPaymentProfile::query()->create([
            'clan_id' => $clan->id,
            'membership_payment_option_id' => $option->id,
            'start_date' => '2026-04-01',
            'membership_amount_override' => 90,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        return [$clan, $admin, $member];
    }
}
