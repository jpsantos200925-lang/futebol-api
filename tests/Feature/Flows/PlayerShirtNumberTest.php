<?php

namespace Tests\Feature\Flows;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerShirtNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_numero_camisa_deve_ser_unico_por_time(): void
    {
        $team = Team::factory()->create();
        Player::factory()->create(['number' => 10, 'team_id' => $team->id]);

        $response = $this->actingAsUser()
            ->postJson('/api/v1/player', [
                'name'    => 'Novo Jogador',
                'number'  => 10,
                'team_id' => $team->id,
            ]);

        $response->assertStatus(400);
        $this->assertDatabaseCount('players', 1);
    }

    public function test_mesmo_numero_em_times_diferentes_e_permitido(): void
    {
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        Player::factory()->create(['number' => 10, 'team_id' => $team1->id]);

        $response = $this->actingAsUser()
            ->postJson('/api/v1/player', [
                'name'    => 'Camisa 10 do Time 2',
                'number'  => 10,
                'team_id' => $team2->id,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseCount('players', 2);
        $this->assertDatabaseHas('players', ['number' => 10, 'team_id' => $team2->id]);
    }

    public function test_update_aceita_mesmo_numero_para_o_proprio_jogador(): void
    {
        $team   = Team::factory()->create();
        $player = Player::factory()->create(['number' => 10, 'team_id' => $team->id]);

        // Atualiza mantendo o mesmo número
        $response = $this->actingAsUser()
            ->patchJson("/api/v1/player/{$player->id}", ['name' => 'Nome Atualizado']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('players', ['id' => $player->id, 'number' => 10, 'name' => 'Nome Atualizado']);
    }
}
