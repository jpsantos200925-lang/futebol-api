<?php

namespace Tests\Unit\Models;

use App\Models\Championship;
use App\Models\ChampionshipMatchs;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_tem_muitos_players(): void
    {
        $team = Team::factory()->create();
        Player::factory()->count(3)->create(['team_id' => $team->id]);

        $this->assertCount(3, $team->players);
    }

    public function test_player_pertence_a_um_team(): void
    {
        $team   = Team::factory()->create(['name' => 'Flamengo']);
        $player = Player::factory()->create(['team_id' => $team->id]);

        $this->assertEquals('Flamengo', $player->team->name);
    }

    public function test_championship_tem_team_id_correto(): void
    {
        $team         = Team::factory()->create();
        $championship = Championship::where('team_id', $team->id)->first();

        $this->assertEquals($team->id, $championship->team_id);
    }

    public function test_championship_match_tem_away_e_home_team_id(): void
    {
        $away  = Team::factory()->create();
        $home  = Team::factory()->create();
        $match = ChampionshipMatchs::factory()->create([
            'away_team_id' => $away->id,
            'home_team_id' => $home->id,
        ]);

        $this->assertEquals($away->id, $match->away_team_id);
        $this->assertEquals($home->id, $match->home_team_id);
    }

    public function test_player_number_cast_como_integer(): void
    {
        $player = Player::factory()->create(['number' => '7']);

        $this->assertIsInt($player->number);
        $this->assertSame(7, $player->number);
    }

    public function test_championship_match_is_ended_cast_como_boolean(): void
    {
        $away  = Team::factory()->create();
        $home  = Team::factory()->create();
        $match = ChampionshipMatchs::factory()->create([
            'away_team_id' => $away->id,
            'home_team_id' => $home->id,
            'is_ended'     => false,
        ]);

        $this->assertIsBool($match->is_ended);
        $this->assertFalse($match->is_ended);
    }

    public function test_championship_pontos_cast_como_integer(): void
    {
        $team         = Team::factory()->create();
        $championship = Championship::where('team_id', $team->id)->first();
        $championship->update(['points' => 9]);
        $championship->refresh();

        $this->assertIsInt($championship->points);
        $this->assertSame(9, $championship->points);
    }

    public function test_championship_todos_os_campos_numericos_cast_como_integer(): void
    {
        $team         = Team::factory()->create();
        $championship = Championship::where('team_id', $team->id)->first();
        $championship->update([
            'points'              => 6,
            'number_of_goals'     => 8,
            'number_of_victories' => 2,
            'number_of_defeats'   => 1,
        ]);
        $championship->refresh();

        $this->assertIsInt($championship->points);
        $this->assertIsInt($championship->number_of_goals);
        $this->assertIsInt($championship->number_of_victories);
        $this->assertIsInt($championship->number_of_defeats);
    }

    public function test_user_hidden_remove_remember_token(): void
    {
        $user = User::factory()->create();

        $this->assertArrayNotHasKey('remember_token', $user->toArray());
    }

    public function test_team_factory_cria_com_nome_unico(): void
    {
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        $this->assertNotEquals($team1->name, $team2->name);
    }
}
