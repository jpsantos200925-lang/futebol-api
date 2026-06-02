<?php

namespace Tests\Unit\Repositories;

use App\Models\ChampionshipMatchs;
use App\Models\Team;
use App\Repositories\ChampionshipMatchsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChampionshipMatchsRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private ChampionshipMatchsRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = app(ChampionshipMatchsRepository::class);
    }

    public function test_delete_by_team_id_remove_partidas_como_mandante(): void
    {
        $home = Team::factory()->create();
        $away = Team::factory()->create();

        ChampionshipMatchs::factory()->create(['home_team_id' => $home->id, 'away_team_id' => $away->id]);
        ChampionshipMatchs::factory()->create(['home_team_id' => $home->id, 'away_team_id' => $away->id]);

        $this->repo->deleteByTeamId($home->id);

        $this->assertDatabaseMissing('championship_matchs', ['home_team_id' => $home->id]);
    }

    public function test_delete_by_team_id_remove_partidas_como_visitante(): void
    {
        $home = Team::factory()->create();
        $away = Team::factory()->create();

        ChampionshipMatchs::factory()->create(['home_team_id' => $home->id, 'away_team_id' => $away->id]);

        $this->repo->deleteByTeamId($away->id);

        $this->assertDatabaseMissing('championship_matchs', ['away_team_id' => $away->id]);
    }

    public function test_delete_by_team_id_nao_remove_partidas_de_outros_times(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $teamC = Team::factory()->create();

        ChampionshipMatchs::factory()->create(['home_team_id' => $teamA->id, 'away_team_id' => $teamB->id]);
        $unrelated = ChampionshipMatchs::factory()->create(['home_team_id' => $teamB->id, 'away_team_id' => $teamC->id]);

        $this->repo->deleteByTeamId($teamA->id);

        $this->assertDatabaseHas('championship_matchs', ['id' => $unrelated->id]);
    }
}
