<?php

namespace Tests\Feature\Http;

use App\Models\Championship;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChampionshipTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------
    // GET /api/v1/championship
    // -------------------------------------------------------

    public function test_index_retorna_tabela_do_campeonato(): void
    {
        $teams = Team::factory()->count(3)->create();

        $response = $this->actingAsUser()->getJson('/api/v1/championship');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_index_retorna_401_sem_autenticacao(): void
    {
        $this->getJson('/api/v1/championship')->assertStatus(401);
    }

    // -------------------------------------------------------
    // GET /api/v1/championship/{id}
    // -------------------------------------------------------

    public function test_show_retorna_championship_por_id(): void
    {
        $team         = Team::factory()->create();
        $championship = Championship::where('team_id', $team->id)->first();

        $response = $this->actingAsUser()
            ->getJson("/api/v1/championship/{$championship->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.team_id', $team->id);
    }

    // -------------------------------------------------------
    // POST /api/v1/championship
    // -------------------------------------------------------

    public function test_store_retorna_422_para_team_id_invalido(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/v1/championship', ['team_id' => 99999]);

        $response->assertStatus(422)->assertJsonValidationErrors(['team_id']);
    }

    // -------------------------------------------------------
    // PATCH /api/v1/championship/{id}
    // -------------------------------------------------------

    public function test_update_atualiza_pontos(): void
    {
        $team         = Team::factory()->create();
        $championship = Championship::where('team_id', $team->id)->first();

        $response = $this->actingAsUser()
            ->patchJson("/api/v1/championship/{$championship->id}", ['points' => 9]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('championships', ['id' => $championship->id, 'points' => 9]);
    }

    // -------------------------------------------------------
    // DELETE /api/v1/championship/{id}
    // -------------------------------------------------------

    public function test_destroy_deleta_championship(): void
    {
        $team         = Team::factory()->create();
        $championship = Championship::where('team_id', $team->id)->first();

        $response = $this->actingAsUser()
            ->deleteJson("/api/v1/championship/{$championship->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('championships', ['id' => $championship->id]);
    }
}
