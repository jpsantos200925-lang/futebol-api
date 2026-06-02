<?php

namespace Tests\Feature\Flows;

use App\Models\Championship;
use App\Models\ChampionshipMatchs;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamDeletionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletar_time_remove_todos_os_dados_associados(): void
    {
        $teamA = Team::factory()->create(['name' => 'Time A']);
        $teamB = Team::factory()->create(['name' => 'Time B']);

        // 3 jogadores no time A
        $players = Player::factory()->count(3)->create(['team_id' => $teamA->id]);

        // 2 partidas envolvendo time A (1 home, 1 away)
        $matchHome = ChampionshipMatchs::factory()->create([
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
        ]);
        $matchAway = ChampionshipMatchs::factory()->create([
            'away_team_id' => $teamA->id,
            'home_team_id' => $teamB->id,
        ]);

        // Confirma que championship existe para o time A
        $this->assertDatabaseHas('championships', ['team_id' => $teamA->id]);

        // Deleta time A
        $this->actingAsUser()
            ->deleteJson("/api/v1/team/{$teamA->id}")
            ->assertStatus(200);

        // Time A sumiu
        $this->assertDatabaseMissing('teams', ['id' => $teamA->id]);

        // Jogadores do time A sumiram
        foreach ($players as $player) {
            $this->assertDatabaseMissing('players', ['id' => $player->id]);
        }

        // Partidas do time A sumiram
        $this->assertDatabaseMissing('championship_matchs', ['id' => $matchHome->id]);
        $this->assertDatabaseMissing('championship_matchs', ['id' => $matchAway->id]);

        // Championship do time A sumiu
        $this->assertDatabaseMissing('championships', ['team_id' => $teamA->id]);

        // Time B e seus dados ficaram intactos
        $this->assertDatabaseHas('teams', ['id' => $teamB->id]);
        $this->assertDatabaseHas('championships', ['team_id' => $teamB->id]);
    }
}
