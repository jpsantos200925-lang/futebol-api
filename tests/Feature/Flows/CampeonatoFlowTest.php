<?php

namespace Tests\Feature\Flows;

use App\Models\Championship;
use App\Models\ChampionshipMatchs;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampeonatoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_fluxo_completo_vitoria_visitante(): void
    {
        $away = Team::factory()->create(['name' => 'Flamengo']);
        $home = Team::factory()->create(['name' => 'Palmeiras']);

        // Championships criados automaticamente pelo TeamObserver
        $this->assertDatabaseHas('championships', ['team_id' => $away->id]);
        $this->assertDatabaseHas('championships', ['team_id' => $home->id]);

        $match = ChampionshipMatchs::factory()->create([
            'away_team_id' => $away->id,
            'home_team_id' => $home->id,
        ]);

        $this->assertFalse($match->is_ended);

        // Finaliza partida: Flamengo 2 x 0 Palmeiras
        $this->actingAsUser()
            ->patchJson("/api/v1/championship-match/{$match->id}", [
                'away_team_goals' => 2,
                'home_team_goals' => 0,
                'is_ended'        => true,
            ])
            ->assertStatus(200);

        $awayChampionship = Championship::where('team_id', $away->id)->first();
        $homeChampionship = Championship::where('team_id', $home->id)->first();

        // Flamengo venceu
        $this->assertEquals(3, $awayChampionship->points);
        $this->assertEquals(2, $awayChampionship->number_of_goals);
        $this->assertEquals(1, $awayChampionship->number_of_victories);
        $this->assertEquals(0, $awayChampionship->number_of_defeats);

        // Palmeiras perdeu
        $this->assertEquals(0, $homeChampionship->points);
        $this->assertEquals(0, $homeChampionship->number_of_goals);
        $this->assertEquals(0, $homeChampionship->number_of_victories);
        $this->assertEquals(1, $homeChampionship->number_of_defeats);
    }

    public function test_empate_distribui_1_ponto_para_cada_time(): void
    {
        $away = Team::factory()->create();
        $home = Team::factory()->create();

        $match = ChampionshipMatchs::factory()->create([
            'away_team_id' => $away->id,
            'home_team_id' => $home->id,
        ]);

        $this->actingAsUser()
            ->patchJson("/api/v1/championship-match/{$match->id}", [
                'away_team_goals' => 1,
                'home_team_goals' => 1,
                'is_ended'        => true,
            ]);

        $awayChampionship = Championship::where('team_id', $away->id)->first();
        $homeChampionship = Championship::where('team_id', $home->id)->first();

        $this->assertEquals(1, $awayChampionship->points);
        $this->assertEquals(1, $homeChampionship->points);
        $this->assertEquals(0, $awayChampionship->number_of_victories);
        $this->assertEquals(0, $awayChampionship->number_of_defeats);
        $this->assertEquals(0, $homeChampionship->number_of_victories);
        $this->assertEquals(0, $homeChampionship->number_of_defeats);
    }

    public function test_multiplas_partidas_acumulam_estatisticas(): void
    {
        $away = Team::factory()->create(['name' => 'Flamengo']);
        $home = Team::factory()->create(['name' => 'Palmeiras']);

        // Partida 1: Flamengo 2x0 Palmeiras → Fla: 3pts, Pal: 0pts
        $match1 = ChampionshipMatchs::factory()->create(['away_team_id' => $away->id, 'home_team_id' => $home->id]);
        $this->actingAsUser()->patchJson("/api/v1/championship-match/{$match1->id}", [
            'away_team_goals' => 2, 'home_team_goals' => 0, 'is_ended' => true,
        ]);

        // Partida 2: Palmeiras 3x1 Flamengo (home=Fla, away=Pal) → Pal: 3pts, Fla: 0pts
        $match2 = ChampionshipMatchs::factory()->create(['away_team_id' => $home->id, 'home_team_id' => $away->id]);
        $this->actingAsUser()->patchJson("/api/v1/championship-match/{$match2->id}", [
            'away_team_goals' => 3, 'home_team_goals' => 1, 'is_ended' => true,
        ]);

        // Partida 3: Flamengo 1x1 Palmeiras → ambos: +1pt
        $match3 = ChampionshipMatchs::factory()->create(['away_team_id' => $away->id, 'home_team_id' => $home->id]);
        $this->actingAsUser()->patchJson("/api/v1/championship-match/{$match3->id}", [
            'away_team_goals' => 1, 'home_team_goals' => 1, 'is_ended' => true,
        ]);

        $flaChampionship = Championship::where('team_id', $away->id)->first();
        $palChampionship = Championship::where('team_id', $home->id)->first();

        // Flamengo: 3+0+1 = 4 pts; gols: 2+1+1 = 4; vitórias: 1; derrotas: 1
        $this->assertEquals(4, $flaChampionship->points);
        $this->assertEquals(4, $flaChampionship->number_of_goals);
        $this->assertEquals(1, $flaChampionship->number_of_victories);
        $this->assertEquals(1, $flaChampionship->number_of_defeats);

        // Palmeiras: 0+3+1 = 4 pts; gols: 0+3+1 = 4; vitórias: 1; derrotas: 1
        $this->assertEquals(4, $palChampionship->points);
        $this->assertEquals(4, $palChampionship->number_of_goals);
        $this->assertEquals(1, $palChampionship->number_of_victories);
        $this->assertEquals(1, $palChampionship->number_of_defeats);
    }
}
