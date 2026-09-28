<?php

namespace Tests\Feature;

use App\Models\Clanovi;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberListFiltersTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_admin_sees_filters_and_correct_license_and_medical_statuses(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 28)->startOfDay());
        $settings = SiteSetting::query()->firstOrCreate([], ['theme_mode_policy' => 'auto']);
        $settings->payment_tracking_enabled = true;
        $settings->save();
        $suffix = Str::lower(Str::random(10));
        $licensedName = 'Licenciran'.$suffix;
        $expiredName = 'Istekao'.$suffix;

        Clanovi::query()->create([
            'Ime' => $licensedName,
            'Prezime' => 'Test',
            'datum_rodjenja' => '1990-01-01',
            'spol' => 'M',
            'oib' => (string) random_int(10000000000, 99999999999),
            'aktivan' => 1,
            'broj_licence' => '1234',
            'lijecnicki_do' => '2026-09-28',
        ]);
        Clanovi::query()->create([
            'Ime' => $expiredName,
            'Prezime' => 'Test',
            'datum_rodjenja' => '1990-01-01',
            'spol' => 'M',
            'oib' => (string) random_int(10000000000, 99999999999),
            'aktivan' => 1,
            'broj_licence' => 'nema licencu',
            'lijecnicki_do' => '2026-09-27',
        ]);

        $admin = $this->user(1, $suffix);
        $response = $this->actingAs($admin)->get(route('javno.clanovi'));

        $response->assertOk()
            ->assertSee('id="filter-clanova-aktivni"', false)
            ->assertSee('id="filter-clanova-neaktivni"', false)
            ->assertSee('value="licensed"', false)
            ->assertSee('value="unlicensed"', false)
            ->assertSee('value="unpaid"', false)
            ->assertSee('value="paid"', false)
            ->assertSee('value="medical_invalid"', false)
            ->assertSee('value="medical_valid"', false)
            ->assertSee('data-payment-state=', false);

        $html = $response->getContent();
        $this->assertMatchesRegularExpression(
            '/data-ime="'.preg_quote($licensedName, '/').'"\s+data-prezime="Test"\s+data-licensed="1"\s+data-medical-valid="1"/',
            $html
        );
        $this->assertMatchesRegularExpression(
            '/data-ime="'.preg_quote($expiredName, '/').'"\s+data-prezime="Test"\s+data-licensed="0"\s+data-medical-valid="0"/',
            $html
        );
    }

    public function test_member_keeps_name_search_without_admin_filters(): void
    {
        $member = $this->user(2, Str::lower(Str::random(10)));

        $this->actingAs($member)
            ->get(route('javno.clanovi'))
            ->assertOk()
            ->assertSee('id="pretraga-clanova-aktivni"', false)
            ->assertSee('Br. članova:')
            ->assertDontSee('class="form-select form-select-sm js-clanovi-filter"', false)
            ->assertDontSee('data-licensed=')
            ->assertDontSee('data-medical-valid=')
            ->assertDontSee('data-payment-state=')
            ->assertDontSee('id="popis-neaktivnih"', false);
    }

    private function user(int $role, string $suffix): User
    {
        return User::query()->create([
            'name' => 'Test korisnik',
            'email' => 'member-filters-'.$role.'-'.$suffix.'@example.test',
            'password' => 'test-password',
            'rola' => $role,
        ]);
    }
}
