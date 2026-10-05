<?php

namespace Tests\Feature;

use App\Models\NadolazeciTurnir;
use App\Models\TipoviTurnira;
use App\Models\Turniri;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class TournamentArchiveTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_admin_can_archive_and_restore_a_past_tournament_without_deleting_it(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5)->startOfDay());
        $admin = $this->user(1);
        $turnir = $this->tournament('2026-10-01');

        $this->actingAs($admin)->get(route('admin.nadolazeci_turniri.index'))
            ->assertOk()->assertSeeText($turnir->naziv);

        $this->post(route('admin.nadolazeci_turniri.archive', $turnir))
            ->assertRedirect(route('admin.nadolazeci_turniri.index'));

        $this->assertNotNull($turnir->fresh()->archived_at);
        $this->get(route('admin.nadolazeci_turniri.index'))
            ->assertOk()->assertDontSeeText($turnir->naziv);
        $this->get(route('admin.nadolazeci_turniri.archive_index'))
            ->assertOk()->assertSeeText($turnir->naziv);

        $this->post(route('admin.nadolazeci_turniri.restore', $turnir))
            ->assertRedirect(route('admin.nadolazeci_turniri.archive_index'));

        $this->assertNull($turnir->fresh()->archived_at);
        $this->get(route('admin.nadolazeci_turniri.index'))
            ->assertOk()->assertSeeText($turnir->naziv);
    }

    public function test_creating_results_archives_the_tournament_and_links_existing_results(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5)->startOfDay());
        $turnir = $this->tournament('2026-10-01');
        $this->actingAs($this->user(1));

        $this->post(route('admin.nadolazeci_turniri.kreiraj_rezultate', $turnir))
            ->assertRedirect();

        $turnir->refresh();
        $this->assertNotNull($turnir->archived_at);
        $this->assertNotNull($turnir->rezultatski_turnir_id);
        $this->assertSame(1, Turniri::query()->whereKey($turnir->rezultatski_turnir_id)->count());
        $this->get(route('admin.nadolazeci_turniri.index'))
            ->assertOk()->assertDontSeeText($turnir->naziv);
        $this->get(route('admin.nadolazeci_turniri.archive_index'))
            ->assertOk()
            ->assertSeeText($turnir->naziv)
            ->assertSee(route('admin.rezultati.unosRezultata', $turnir->rezultatski_turnir_id));

        $this->post(route('admin.nadolazeci_turniri.kreiraj_rezultate', $turnir))
            ->assertRedirect(route('admin.rezultati.unosRezultata', $turnir->rezultatski_turnir_id));
        $this->assertSame(1, Turniri::query()
            ->where('naziv', $turnir->naziv)
            ->whereDate('datum', '2026-10-01')
            ->count());
    }

    public function test_future_tournaments_cannot_be_archived_and_members_cannot_access_the_archive(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 5)->startOfDay());
        $turnir = $this->tournament('2026-10-10');

        $this->actingAs($this->user(1))
            ->post(route('admin.nadolazeci_turniri.archive', $turnir))
            ->assertRedirect(route('admin.nadolazeci_turniri.index'))
            ->assertSessionHas('error');
        $this->assertNull($turnir->fresh()->archived_at);

        $this->actingAs($this->user(2))
            ->get(route('admin.nadolazeci_turniri.archive_index'))
            ->assertForbidden();
        $this->post(route('admin.nadolazeci_turniri.archive', $turnir))
            ->assertForbidden();
    }

    private function tournament(string $date): NadolazeciTurnir
    {
        $unique = Str::lower(Str::random(12));
        $type = TipoviTurnira::query()->create(['naziv' => 'Test tip '.$unique]);

        return NadolazeciTurnir::query()->create([
            'naziv' => 'Arhiva test '.$unique,
            'mjesto' => 'Zagreb',
            'datum' => $date,
            'tipovi_turnira_id' => $type->id,
        ]);
    }

    private function user(int $role): User
    {
        return User::query()->create([
            'name' => 'Test korisnik',
            'email' => 'tournament-archive-'.Str::lower(Str::random(12)).'@example.test',
            'password' => 'test-password',
            'rola' => $role,
        ]);
    }
}
