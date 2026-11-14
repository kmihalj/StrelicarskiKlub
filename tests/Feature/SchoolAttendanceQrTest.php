<?php

namespace Tests\Feature;

use App\Models\PolaznikSkole;
use App\Models\PolaznikSkoleDolazak;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class SchoolAttendanceQrTest extends TestCase
{
    use DatabaseTransactions;

    public function test_card_is_reusable_and_visible_only_to_the_student_and_linked_parent(): void
    {
        $student = $this->student();
        $account = $this->user(4, ['polaznik_id' => $student->id]);
        $parent = $this->user(2, ['je_roditelj' => true]);
        $parent->djecaPolaznici()->attach($student);
        $url = route('skola.polaznici.qr_dolasci', $student);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->user(2, ['je_roditelj' => true]))->get($url)->assertForbidden();
        $home = route('javno.naslovnaStranica');
        $this->get($home)->assertOk()->assertDontSee($url);
        $this->actingAs($account)->get($home)->assertOk()->assertSeeText('QR Evidencija dolazaka')->assertSee($url);
        $qr = $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('no-store', $qr->headers->get('Cache-Control'));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $student->fresh()->qr_dolazak_token);
        $this->actingAs($parent)->get($home)->assertOk()->assertSee($url);
        $this->assertSame($qr->getContent(), $this->get($url)->assertOk()->getContent());

        $student->update(['u_skoli' => false]);
        $this->get($url)->assertNotFound();
    }

    public function test_scans_fill_the_next_slot_once_per_zagreb_day_and_require_an_admin(): void
    {
        $this->travelTo(Carbon::parse('2026-11-14 23:10:00', 'UTC'), function (): void {
            $student = $this->student();
            $student->dolasci()->create(['redni_broj' => 1, 'datum' => '2026-11-14']);
            $student->dolasci()->create(['redni_broj' => 2]);
            $url = route('admin.skola.evidencija.qr.spremi');
            $code = ['kod' => $this->code($student)];

            $this->postJson($url, $code)->assertUnauthorized();
            $this->actingAs($this->user(4, ['polaznik_id' => $student->id]))
                ->postJson($url, $code)->assertForbidden();
            $this->actingAs($this->user(1))->postJson($url, $code)
                ->assertCreated()->assertJsonPath('status', 'evidentiran')->assertJsonPath('datum', '15.11.2026.');
            $this->postJson($url, $code)->assertOk()->assertJsonPath('status', 'vec_evidentiran');
            $this->assertSame(2, $student->dolasci()->whereNotNull('datum')->count());
            $this->assertDatabaseHas('polaznici_skole_dolasci', [
                'polaznik_skole_id' => $student->id, 'redni_broj' => 2, 'datum' => '2026-11-15',
            ]);
            $this->postJson($url, ['kod' => 'https://example.test'])->assertUnprocessable();
            $this->postJson($url, ['kod' => 'SKD1:'.str_repeat('f', 32)])->assertNotFound();
        });
    }

    public function test_admin_can_show_and_scan_own_preview_without_recording_attendance(): void
    {
        $preview = route('admin.skola.evidencija.qr.probni');
        $scan = route('admin.skola.evidencija.qr.spremi');
        $admin = $this->user(1, ['name' => 'Krešimir Mihalj']);
        $code = 'SKDTEST1:'.substr(hash_hmac('sha256', 'skd-qr-probni:'.$admin->id, config('app.key')), 0, 32);
        $before = PolaznikSkoleDolazak::query()->whereNotNull('datum')->count();

        $this->get($preview)->assertRedirect(route('login'));
        $this->actingAs($this->user(2))->get($preview)->assertForbidden();
        $this->actingAs($admin)->get(route('javno.naslovnaStranica'))->assertOk()->assertSee($preview);
        $this->get($preview)->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
        $this->postJson($scan, ['kod' => $code])->assertOk()
            ->assertJsonPath('status', 'probni')->assertJsonPath('polaznik', 'Krešimir Mihalj');
        $this->actingAs($this->user(1))->postJson($scan, ['kod' => $code])->assertNotFound();
        $this->assertSame($before, PolaznikSkoleDolazak::query()->whereNotNull('datum')->count());
    }

    public function test_existing_manual_attendance_full_and_inactive_records_are_handled(): void
    {
        $student = $this->student();
        $code = ['kod' => $this->code($student)];
        $url = route('admin.skola.evidencija.qr.spremi');
        $this->actingAs($this->user(1));
        $student->dolasci()->create(['redni_broj' => 1, 'datum' => today('Europe/Zagreb')->toDateString()]);
        $this->postJson($url, $code)->assertOk()->assertJsonPath('status', 'vec_evidentiran');
        $this->assertSame(1, $student->dolasci()->count());

        for ($slot = 2; $slot <= 16; $slot++) {
            $student->dolasci()->create(['redni_broj' => $slot, 'datum' => today('Europe/Zagreb')->subDays($slot)->toDateString()]);
        }
        $this->travel(1)->day();
        $this->postJson($url, $code)->assertConflict();
        $this->assertSame(16, $student->dolasci()->count());
        $student->update(['u_skoli' => false]);
        $this->postJson($url, $code)->assertNotFound();
    }

    public function test_manual_form_rejects_stale_and_duplicate_dates_and_supports_reordering(): void
    {
        $student = $this->student();
        $oldDate = today('Europe/Zagreb')->subDay()->toDateString();
        $today = today('Europe/Zagreb')->toDateString();
        $student->dolasci()->create(['redni_broj' => 1, 'datum' => $oldDate]);
        $version = PolaznikSkoleDolazak::verzija($student->dolasci()->get());
        $this->actingAs($this->user(1))->postJson(route('admin.skola.evidencija.qr.spremi'), ['kod' => $this->code($student)])->assertCreated();
        $url = route('admin.skola.evidencija.spremi');

        $this->post($url, ['dolasci' => [$student->id => [1 => $oldDate]], 'dolasci_verzija' => [$student->id => $version]])
            ->assertSessionHas('error');
        $this->assertSame(2, $student->dolasci()->whereNotNull('datum')->count());
        $version = PolaznikSkoleDolazak::verzija($student->dolasci()->get());

        $this->post($url, ['dolasci' => [$student->id => [1 => $today, 2 => $today]], 'dolasci_verzija' => [$student->id => $version]])
            ->assertSessionHas('error');
        $this->post($url, ['dolasci' => [$student->id => [1 => $today, 2 => $oldDate]], 'dolasci_verzija' => [$student->id => $version]])
            ->assertSessionHas('success');
        $this->assertDatabaseHas('polaznici_skole_dolasci', [
            'polaznik_skole_id' => $student->id, 'redni_broj' => 1, 'datum' => $today,
        ]);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function student(): PolaznikSkole
    {
        return PolaznikSkole::query()->create([
            'Ime' => 'Test', 'Prezime' => 'QR', 'oib' => (string) random_int(10000000000, 99999999999), 'u_skoli' => true,
        ]);
    }

    private function user(int $role, array $attributes = []): User
    {
        return User::query()->create($attributes + [
            'name' => 'Test QR', 'email' => Str::uuid().'@example.test', 'password' => 'test-password', 'rola' => $role,
        ]);
    }

    private function code(PolaznikSkole $student): string
    {
        $student->qr_dolazak_token = bin2hex(random_bytes(16));
        $student->save();

        return 'SKD1:'.$student->qr_dolazak_token;
    }
}
