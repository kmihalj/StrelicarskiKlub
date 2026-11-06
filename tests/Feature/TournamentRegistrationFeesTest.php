<?php

namespace Tests\Feature;

use App\Models\Clanovi;
use App\Models\ClanPaymentCharge;
use App\Models\Kategorije;
use App\Models\NadolazeciTurnir;
use App\Models\PrijavaTurnira;
use App\Models\RezultatiOpci;
use App\Models\SiteSetting;
use App\Models\Stilovi;
use App\Models\TipoviTurnira;
use App\Models\User;
use App\Services\PaymentTrackingService as Payments;
use App\Support\TournamentFeeCategories as Fees;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class TournamentRegistrationFeesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->travelTo(now()->setDate(2026, 11, 5)->startOfDay());
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_male_and_female_competition_categories_use_the_same_age_group_fee(): void
    {
        $turnir = $this->tournament();
        foreach (['M' => 'M', 'W' => 'Ž'] as $suffix => $gender) {
            $user = $this->member($gender);
            $category = $this->category('U13'.$suffix, $gender);
            $response = $this->actingAs($user)->post(route('javno.prijave_turnira.store'), $this->registration($user, $turnir, $category));
            $response->assertRedirect()->assertSessionHasNoErrors();
            $entry = PrijavaTurnira::query()->where('clan_id', $user->clan_id)->sole();
            $this->assertSame($category->id, $entry->kategorija_id);
            $this->assertSame('U13', Fees::groupFor($entry->kategorija));
            $this->assertSame('15.00', $entry->paymentCharge->amount);
            $this->get(route('javno.prijave_turnira.show', $entry))->assertOk()->assertSeeText('15,00 EUR')->assertSeeText($category->naziv);
            $this->get(route('javno.prijave_turnira.index'))->assertOk()->assertSeeText('15,00 €');
        }
        foreach (['U11M' => 'U11', 'U15W' => 'U15', 'U18M' => 'U18', 'U21W' => 'U21', 'Seniori (M)' => 'seniori', 'Seniorke (W)' => 'seniori', 'Veterani (M50+)' => 'veterani', 'Veteranke (W50+)' => 'veterani'] as $name => $group) {
            $this->assertSame($group, Fees::groupFor($name));
        }
    }

    public function test_each_style_has_a_separate_registration_and_charge_for_all_schedules(): void
    {
        foreach (['same_shift', 'different_shifts', 'two_days'] as $schedule) {
            $user = $this->member();
            $turnir = $this->tournament($schedule === 'two_days' ? ['datum_do' => '2026-11-15'] : []);
            $category = $this->category('Seniori (M)', 'M');
            $first = $this->registration($user, $turnir, $category);
            $second = array_replace($first, ['stil_id' => $this->style()->id]);
            if ($schedule === 'different_shifts') {
                $second['smjena'] = 'popodnevna';
            }
            if ($schedule === 'two_days') {
                $first['odabrani_dan'] = '2026-11-14';
                $second['odabrani_dan'] = '2026-11-15';
            }
            $this->actingAs($user)->post(route('javno.prijave_turnira.store'), $first)->assertRedirect()->assertSessionHasNoErrors();
            $this->post(route('javno.prijave_turnira.store'), $second)->assertRedirect()->assertSessionHasNoErrors();
            $entries = PrijavaTurnira::query()->where('clan_id', $user->clan_id)->orderBy('id')->get();
            $this->assertCount(2, $entries);
            $this->assertNotSame($entries[0]->clan_payment_charge_id, $entries[1]->clan_payment_charge_id);
            $this->assertSame(40.0, (float) $entries->sum(fn ($entry) => $entry->paymentCharge->amount));
            $this->post(route('javno.prijave_turnira.store'), $second)->assertSessionHas('error');
            $this->assertSame(2, PrijavaTurnira::query()->where('clan_id', $user->clan_id)->count());
            $this->post(route('javno.prijave_turnira.update', $entries[1]), $first)->assertSessionHasErrors('stil_id');
            $this->post(route('javno.prijave_turnira.odjava', $entries[0]))->assertRedirect();
            $this->assertSame(Payments::STATUS_DELETED, $entries[0]->paymentCharge->fresh()->status);
            $this->assertSame(Payments::STATUS_OPEN, $entries[1]->paymentCharge->fresh()->status);
            $this->post(route('javno.prijave_turnira.store'), $first)->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame(2, PrijavaTurnira::query()->where('clan_id', $user->clan_id)->count());
            $this->assertSame(Payments::STATUS_OPEN, $entries[0]->paymentCharge->fresh()->status);
        }
    }

    public function test_paid_legacy_charge_is_not_reused_by_a_second_style_or_rewritten_when_rates_change(): void
    {
        $user = $this->member();
        $turnir = $this->tournament();
        $category = $this->category('Seniori (M)', 'M');
        $first = $this->registration($user, $turnir, $category);
        $this->actingAs($user)->post(route('javno.prijave_turnira.store'), $first)->assertRedirect();
        $entry = PrijavaTurnira::query()->where('clan_id', $user->clan_id)->sole();
        $charge = $entry->paymentCharge;
        $charge->update(['period_key' => 'turnir-'.$turnir->id, 'status' => Payments::STATUS_PAID, 'paid_at' => now()]);
        $this->post(route('javno.prijave_turnira.odjava', $entry))->assertRedirect();
        $this->post(route('javno.prijave_turnira.store'), array_replace($first, ['stil_id' => $this->style()->id]))->assertRedirect()->assertSessionHasNoErrors();
        $second = PrijavaTurnira::query()->where('clan_id', $user->clan_id)->where('id', '!=', $entry->id)->sole();
        $this->assertNotSame($charge->id, $second->clan_payment_charge_id);
        $this->assertSame(Payments::STATUS_OPEN, $second->paymentCharge->status);
        $this->post(route('javno.prijave_turnira.store'), $first)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($charge->id, $entry->fresh()->clan_payment_charge_id);
        $this->assertSame(Payments::STATUS_PAID, $charge->fresh()->status);

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.nadolazeci_turniri.update', $turnir), $this->adminData($turnir, [
            'kotizacija_razredi' => [['kategorije' => array_keys(Fees::LABELS), 'iznos' => '25']],
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('20.00', $charge->fresh()->amount);
        $this->assertSame('25.00', $second->paymentCharge->fresh()->amount);

        $this->actingAs($user)->post(route('javno.prijave_turnira.update', $entry), array_replace($first, [
            'napomena_clana' => 'Promjena napomene nakon uplate',
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Promjena napomene nakon uplate', $entry->fresh()->napomena_clana);
        $this->assertSame('20.00', $charge->fresh()->amount);
    }

    public function test_repricing_existing_fee_updates_payment_form_barcode_and_confirmation(): void
    {
        $settings = SiteSetting::query()->firstOrCreate([], ['theme_mode_policy' => 'auto']);
        $settings->update(['payment_tracking_enabled' => true]);
        $user = $this->member('Ž');
        $turnir = $this->tournament(['kotizacija_iznos' => '20.00', 'kotizacija_razredi' => null]);
        $category = $this->category('U13W', 'Ž');
        $this->actingAs($user)->post(route('javno.prijave_turnira.store'), $this->registration($user, $turnir, $category))
            ->assertRedirect()->assertSessionHasNoErrors();
        $entry = PrijavaTurnira::query()->where('clan_id', $user->clan_id)->sole();
        $charge = $entry->paymentCharge;
        $charge->update(['period_key' => 'turnir-'.$turnir->id, 'metadata' => array_replace($charge->metadata, [
            'base_amount' => 20, 'base_title' => 'Stara kotizacija',
        ])]);

        $this->actingAs($this->admin())->post(route('admin.nadolazeci_turniri.update', $turnir), $this->adminData($turnir, [
            'kotizacija_razredi' => [
                ['kategorije' => ['U11', 'U13', 'U15'], 'iznos' => '15.00'],
                ['kategorije' => ['U18', 'U21', 'seniori', 'veterani'], 'iznos' => '20.00'],
            ],
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $charge->refresh();
        $this->assertSame('15.00', $charge->amount);
        $service = app(Payments::class);
        $this->assertSame(15.0, $service->resolvedChargeAmount($charge, true));
        $this->assertSame(15.0, (float) $charge->metadata['base_amount']);
        $this->assertSame($charge->title, $charge->metadata['base_title']);
        $html = $this->get(route('admin.clanovi.prikaz_clana', $user->clan).'?open_payments=1')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/data-charge-id="'.$charge->id.'"[^>]*value="15\.00"/s', $html);

        $charge->update(['metadata' => array_replace($charge->metadata, [
            'payment_recipient_name' => 'Test klub', 'payment_recipient_iban' => 'HR1210010051863000160',
        ])]);
        $this->assertSame('15,00', $service->buildHubPayloadForCharge($user->clan, $charge)['fields']['iznos']);
        $this->post(route('admin.clanovi.placanja.status', [$user->clan, $charge]), [
            'is_paid' => '1', 'paid_at' => '2026-11-05',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(Payments::STATUS_PAID, $charge->fresh()->status);
        $this->assertSame('15.00', $charge->fresh()->amount);
        $this->assertSame($entry->fresh()->paymentCharge->title, $charge->metadata['base_title']);
        $this->post(route('admin.clanovi.placanja.status', [$user->clan, $charge]), ['is_paid' => '0'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('15.00', $charge->fresh()->amount);
    }

    public function test_category_changes_reprice_only_that_registration_and_paid_changes_are_guarded(): void
    {
        $user = $this->member();
        $turnir = $this->tournament();
        $category = $this->category('U13M', 'M');
        $data = $this->registration($user, $turnir, $category);
        $this->actingAs($user)->post(route('javno.prijave_turnira.store'), $data)->assertRedirect();
        $entry = PrijavaTurnira::query()->where('clan_id', $user->clan_id)->sole();
        $senior = $this->category('Seniori (M)', 'M');
        $this->post(route('javno.prijave_turnira.update', $entry), array_replace($data, ['kategorija_id' => $senior->id]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('20.00', $entry->paymentCharge->fresh()->amount);
        $entry->paymentCharge->update(['status' => Payments::STATUS_PAID, 'paid_at' => now()]);
        $this->post(route('javno.prijave_turnira.update', $entry), $data)->assertSessionHasErrors('kategorija_id');
        $this->assertSame($senior->id, $entry->fresh()->kategorija_id);
        $this->assertSame('20.00', $entry->paymentCharge->fresh()->amount);
    }

    public function test_admin_can_save_three_fee_groups_and_reject_invalid_or_incomplete_assignments(): void
    {
        $turnir = $this->tournament();
        $this->actingAs($this->admin());
        $groups = [
            ['kategorije' => ['U11', 'U13'], 'iznos' => '10,50'],
            ['kategorije' => ['U15', 'U18', 'U21'], 'iznos' => '15'],
            ['kategorije' => ['seniori', 'veterani'], 'iznos' => '20'],
        ];
        $this->post(route('admin.nadolazeci_turniri.update', $turnir), $this->adminData($turnir, ['kotizacija_razredi' => $groups]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertCount(3, $turnir->fresh()->kotizacija_razredi);
        $this->assertSame(10.5, $turnir->fresh()->kotizacijaZaKategoriju(new Kategorije(['naziv' => 'U13W'])));
        $this->assertSame(15.0, $turnir->fresh()->kotizacijaZaKategoriju(new Kategorije(['naziv' => 'U18M'])));
        foreach ([
            [['kategorije' => ['U11'], 'iznos' => '15']],
            [['kategorije' => array_keys(Fees::LABELS), 'iznos' => '-1']],
            [['kategorije' => array_keys(Fees::LABELS), 'iznos' => '15'], ['kategorije' => ['U11'], 'iznos' => '10']],
            [['kategorije' => array_keys(Fees::LABELS), 'iznos' => 'abc']],
        ] as $invalid) {
            $this->post(route('admin.nadolazeci_turniri.update', $turnir), $this->adminData($turnir, ['kotizacija_razredi' => $invalid]))->assertSessionHasErrors();
            $this->assertCount(3, $turnir->fresh()->kotizacija_razredi);
        }
    }

    public function test_parent_can_register_two_styles_for_own_child_but_not_another_member(): void
    {
        $child = $this->member('Ž');
        $parent = User::query()->create(['name' => 'Test roditelj', 'email' => Str::uuid().'@example.test', 'password' => 'test-password', 'rola' => 2, 'je_roditelj' => true]);
        $parent->djecaClanovi()->attach($child->clan_id);
        $turnir = $this->tournament();
        $category = $this->category('U13W', 'Ž');
        $data = $this->registration($child, $turnir, $category);
        $this->actingAs($parent)->post(route('javno.prijave_turnira.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('javno.prijave_turnira.store'), array_replace($data, ['stil_id' => $this->style()->id]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, PrijavaTurnira::query()->where('clan_id', $child->clan_id)->count());
        $other = $this->member('Ž');
        $this->post(route('javno.prijave_turnira.store'), array_replace($data, ['clan_id' => $other->clan_id]))->assertForbidden();
    }

    public function test_cash_and_undefined_fees_do_not_create_bank_charges(): void
    {
        foreach (['cash', null] as $method) {
            $user = $this->member('Ž');
            $settings = ['kotizacija_nacin' => $method];
            if ($method === null) {
                $settings += ['kotizacija_iznos' => null, 'kotizacija_razredi' => null];
            }
            $turnir = $this->tournament($settings);
            $category = $this->category('U13W', 'Ž');
            $this->actingAs($user)->post(route('javno.prijave_turnira.store'), $this->registration($user, $turnir, $category))
                ->assertRedirect()->assertSessionHasNoErrors();
            $entry = PrijavaTurnira::query()->where('clan_id', $user->clan_id)->sole();
            $this->assertNull($entry->clan_payment_charge_id);
            $this->assertSame($method === 'cash' ? 15.0 : null, $entry->iznosKotizacije());
            $this->assertSame(0, ClanPaymentCharge::query()->where('clan_id', $user->clan_id)
                ->where('source', Payments::SOURCE_TOURNAMENT_FEE)->count());
        }
    }

    public function test_admin_exports_and_creates_separate_results_for_each_style(): void
    {
        $user = $this->member();
        $turnir = $this->tournament();
        $category = $this->category('Seniori (M)', 'M');
        $data = $this->registration($user, $turnir, $category);
        $this->actingAs($this->admin());
        foreach ([$data['stil_id'], $this->style()->id] as $style) {
            $this->post(route('admin.nadolazeci_turniri.prijave.admin_dodaj', $turnir), array_replace($data, ['stil_id' => $style]))->assertRedirect()->assertSessionHasNoErrors();
            $this->get(route('admin.nadolazeci_turniri.show', $turnir))->assertOk()
                ->assertViewHas('adminClanoviZaPrijavu', fn ($clanovi) => $clanovi->contains('id', $user->clan_id))
                ->assertViewHas('adminAktivniStiloviPoClanu', fn ($stilovi) => in_array($style, $stilovi[$user->clan_id], true));
        }
        $this->assertSame(2, $turnir->prijave()->count());
        $csv = $this->get(route('admin.nadolazeci_turniri.export_csv', $turnir))->assertOk()->streamedContent();
        $this->assertSame(2, substr_count($csv, $user->clan->Prezime));
        $this->travelTo(now()->setDate(2026, 11, 16));
        $this->post(route('admin.nadolazeci_turniri.kreiraj_rezultate', $turnir))->assertRedirect();
        $this->assertSame(2, RezultatiOpci::query()->where('turnir_id', $turnir->fresh()->rezultatski_turnir_id)->where('clan_id', $user->clan_id)->count());
    }

    private function tournament(array $overrides = []): NadolazeciTurnir
    {
        return NadolazeciTurnir::query()->create(array_replace([
            'naziv' => 'Test kotizacije '.Str::random(10), 'mjesto' => 'Zagreb',
            'datum' => '2026-11-14', 'prijave_otvorene_do' => '2026-11-10',
            'tipovi_turnira_id' => TipoviTurnira::query()->create(['naziv' => 'Test tip '.Str::random(10)])->id,
            'kotizacija_nacin' => 'bank', 'kotizacija_iznos' => '15.00',
            'kotizacija_razredi' => [
                ['kategorije' => ['U11', 'U13', 'U15'], 'iznos' => '15.00'],
                ['kategorije' => ['U18', 'U21', 'seniori', 'veterani'], 'iznos' => '20.00'],
            ],
        ], $overrides));
    }

    private function member(string $gender = 'M'): User
    {
        $clan = Clanovi::query()->create([
            'Ime' => 'Test', 'Prezime' => 'Kotizacija'.Str::random(10),
            'spol' => $gender, 'datum_rodjenja' => '2014-01-01',
            'oib' => (string) random_int(10000000000, 99999999999),
            'aktivan' => 1, 'lijecnicki_do' => '2027-12-31',
        ]);

        return User::query()->create(['name' => 'Test član', 'email' => Str::uuid().'@example.test', 'password' => 'test-password', 'rola' => 2, 'clan_id' => $clan->id]);
    }

    private function admin(): User
    {
        return User::query()->create(['name' => 'Test admin', 'email' => Str::uuid().'@example.test', 'password' => 'test-password', 'rola' => 1]);
    }

    private function category(string $name, string $gender): Kategorije
    {
        return Kategorije::query()->create(['naziv' => $name.' '.Str::random(8), 'spol' => $gender]);
    }

    private function style(): Stilovi
    {
        do {
            $style = Stilovi::query()->create(['naziv' => 'Test stil '.Str::random(8)]);
        } while ($style->id === 7);

        return $style;
    }

    private function registration(User $user, NadolazeciTurnir $turnir, Kategorije $category): array
    {
        return ['clan_id' => $user->clan_id, 'nadolazeci_turnir_id' => $turnir->id,
            'kategorija_id' => $category->id, 'stil_id' => $this->style()->id, 'smjena' => 'jutarnja'];
    }

    private function adminData(NadolazeciTurnir $turnir, array $overrides = []): array
    {
        return array_replace(['naziv' => $turnir->naziv, 'mjesto' => $turnir->mjesto,
            'datum' => $turnir->datum->toDateString(), 'tipovi_turnira_id' => $turnir->tipovi_turnira_id,
            'kotizacija_nacin' => 'bank', 'kotizacija_razredi' => $turnir->kotizacija_razredi], $overrides);
    }
}
