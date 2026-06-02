<?php

namespace Tests\Feature\Http;

use App\Models\Championship;
use App\Models\ChampionshipMatchs;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChampionshipMatchTest extends TestCase
{
    use RefreshDatabase;

    private function createTwoTeams(): array
    {
        $away = Team::factory()->create();
        $home = Team::factory()->create();
        return [$away, $home];
    }

    // -------------------------------------------------------
    // GET /api/v1/championship-match
    // -------------------------------------------------------

    public function test_index_lista_todas_as_partidas(): void
    {
        [$away, $home] = $this->createTwoTeams();
        ChampionshipMatchs::factory()->count(2)->create(['away_team_id' => $away->id, 'home_team_id' => $home->id]);

        $response = $this->actingAsUser()->getJson('/api/v1/championship-match');

        $response->assertStatus(200)->assertJsonCount(2, 'data');
    }

    public function test_index_retorna_401_sem_autenticacao(): void
    {
        $this->getJson('/api/v1/championship-match')->assertStatus(401);
    }

    // -------------------------------------------------------
    // POST /api/v1/championship-match
    // -------------------------------------------------------

    public function test_store_cria_partida_com_dados_validos(): void
    {
        [$away, $home] = $this->createTwoTeams();

        $response = $this->actingAsUser()
            ->postJson('/api/v1/championship-match', [
                'date'         => '2025-06-15',
                'start_time'   => '15:00',
                'away_team_id' => $away->id,
                'home_team_id' => $home->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('championship_matchs', [
            'away_team_id' => $away->id,
            'home_team_id' => $home->id,
            'is_ended'     => false,
        ]);
    }

    public function test_store_retorna_422_quando_home_igual_away(): void
    {
        $team = Team::factory()->create();

        $response = $this->actingAsUser()
            ->postJson('/api/v1/championship-match', [
                'date'         => '2025-06-15',
                'start_time'   => '15:00',
                'away_team_id' => $team->id,
                'home_team_id' => $team->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_store_retorna_422_para_data_em_formato_invalido(): void
    {
        [$away, $home] = $this->createTwoTeams();

        $response = $this->actingAsUser()
            ->postJson('/api/v1/championship-match', [
                'date'         => '15/06/2025',
                'start_time'   => '15:00',
                'away_team_id' => $away->id,
                'home_team_id' => $home->id,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['date']);
    }

    public function test_store_retorna_422_para_time_inexistente(): void
    {
        $team = Team::factory()->create();

        $response = $this->actingAsUser()
            ->postJson('/api/v1/championship-match', [
                'date'         => '2025-06-15',
                'start_time'   => '15:00',
                'away_team_id' => 99999,
                'home_team_id' => $team->id,
            ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['away_team_id']);
    }

    // -------------------------------------------------------
    // GET /api/v1/championship-match/{id}
    // -------------------------------------------------------

    public function test_show_retorna_partida_por_id(): void
    {
        [$away, $home] = $this->createTwoTeams();
        $match = ChampionshipMatchs::factory()->create(['away_team_id' => $away->id, 'home_team_id' => $home->id]);

        $response = $this->actingAsUser()->getJson("/api/v1/championship-match/{$match->id}");

        $response->assertStatus(200)->assertJsonPath('data.id', $match->id);
    }

    // -------------------------------------------------------
    // PATCH /api/v1/championship-match/{id}
    // -------------------------------------------------------

    public function test_update_atualiza_gols_de_partida_em_andamento(): void
    {
        [$away, $home] = $this->createTwoTeams();
        $match = ChampionshipMatchs::factory()->create(['away_team_id' => $away->id, 'home_team_id' => $home->id]);

        $response = $this->actingAsUser()
            ->patchJson("/api/v1/championship-match/{$match->id}", [
                'away_team_goals' => 2,
                'home_team_goals' => 1,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('championship_matchs', [
            'id'              => $match->id,
            'away_team_goals' => 2,
            'home_team_goals' => 1,
        ]);
    }

    public function test_update_finaliza_partida_e_atualiza_tabela_do_campeonato(): void
    {
        [$away, $home] = $this->createTwoTeams();
        $match = ChampionshipMatchs::factory()->create([
            'away_team_id' => $away->id,
            'home_team_id' => $home->id,
        ]);

        $response = $this->actingAsUser()
            ->patchJson("/api/v1/championship-match/{$match->id}", [
                'away_team_goals' => 3,
                'home_team_goals' => 0,
                'is_ended'        => true,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('championship_matchs', ['id' => $match->id, 'is_ended' => true]);

        $awayChampionship = Championship::where('team_id', $away->id)->first();
        $homeChampionship = Championship::where('team_id', $home->id)->first();

        $this->assertEquals(3, $awayChampionship->points);
        $this->assertEquals(1, $awayChampionship->number_of_victories);
        $this->assertEquals(0, $homeChampionship->points);
        $this->assertEquals(1, $homeChampionship->number_of_defeats);
    }

    public function test_update_retorna_erro_para_partida_ja_finalizada(): void
    {
        [$away, $home] = $this->createTwoTeams();
        $match = ChampionshipMatchs::factory()->ended()->create([
            'away_team_id' => $away->id,
            'home_team_id' => $home->id,
        ]);

        $response = $this->actingAsUser()
            ->patchJson("/api/v1/championship-match/{$match->id}", ['away_team_goals' => 5]);

        $response->assertStatus(404);
    }

    public function test_update_retorna_422_para_gols_negativos(): void
    {
        [$away, $home] = $this->createTwoTeams();
        $match = ChampionshipMatchs::factory()->create(['away_team_id' => $away->id, 'home_team_id' => $home->id]);

        $response = $this->actingAsUser()
            ->patchJson("/api/v1/championship-match/{$match->id}", ['away_team_goals' => -1]);

        $response->assertStatus(422)->assertJsonValidationErrors(['away_team_goals']);
    }

    // -------------------------------------------------------
    // DELETE /api/v1/championship-match/{id}
    // -------------------------------------------------------

    public function test_destroy_deleta_partida(): void
    {
        [$away, $home] = $this->createTwoTeams();
        $match = ChampionshipMatchs::factory()->create(['away_team_id' => $away->id, 'home_team_id' => $home->id]);

        $response = $this->actingAsUser()->deleteJson("/api/v1/championship-match/{$match->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('championship_matchs', ['id' => $match->id]);
    }

    public function test_delete_retorna_401_sem_autenticacao(): void
    {
        [$away, $home] = $this->createTwoTeams();
        $match = ChampionshipMatchs::factory()->create(['away_team_id' => $away->id, 'home_team_id' => $home->id]);

        $this->deleteJson("/api/v1/championship-match/{$match->id}")->assertStatus(401);
    }
}
