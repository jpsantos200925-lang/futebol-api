<?php

namespace Tests\Feature\Http;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    use RefreshDatabase;

    private function playerPayload(array $overrides = []): array
    {
        return array_merge(['name' => 'João Silva', 'number' => 7], $overrides);
    }

    // -------------------------------------------------------
    // GET /api/v1/player
    // -------------------------------------------------------

    public function test_index_retorna_lista_de_jogadores(): void
    {
        $team = Team::factory()->create();
        Player::factory()->count(3)->create(['team_id' => $team->id]);

        $response = $this->actingAsUser()->getJson('/api/v1/player');

        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_index_retorna_401_sem_autenticacao(): void
    {
        $this->getJson('/api/v1/player')->assertStatus(401);
    }

    // -------------------------------------------------------
    // POST /api/v1/player
    // -------------------------------------------------------

    public function test_store_cria_jogador_com_dados_validos(): void
    {
        $team = Team::factory()->create();

        $response = $this->actingAsUser()
            ->postJson('/api/v1/player', $this->playerPayload(['team_id' => $team->id]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('players', ['number' => 7, 'team_id' => $team->id]);
    }

    public function test_store_retorna_erro_para_numero_duplicado_no_mesmo_time(): void
    {
        $team = Team::factory()->create();
        Player::factory()->create(['number' => 10, 'team_id' => $team->id]);

        $response = $this->actingAsUser()
            ->postJson('/api/v1/player', $this->playerPayload(['number' => 10, 'team_id' => $team->id]));

        $response->assertStatus(400);
    }

    public function test_store_permite_mesmo_numero_em_times_diferentes(): void
    {
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();
        Player::factory()->create(['number' => 10, 'team_id' => $team1->id]);

        $response = $this->actingAsUser()
            ->postJson('/api/v1/player', $this->playerPayload(['number' => 10, 'team_id' => $team2->id]));

        $response->assertStatus(201);
    }

    public function test_store_retorna_422_sem_team_id(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/v1/player', ['name' => 'João', 'number' => 7]);

        $response->assertStatus(422)->assertJsonValidationErrors(['0.team_id']);
    }

    public function test_store_retorna_422_para_team_id_inexistente(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/v1/player', $this->playerPayload(['team_id' => 99999]));

        $response->assertStatus(422)->assertJsonValidationErrors(['0.team_id']);
    }

    public function test_store_retorna_401_sem_autenticacao(): void
    {
        $this->postJson('/api/v1/player', [])->assertStatus(401);
    }

    // -------------------------------------------------------
    // GET /api/v1/player/{id}
    // -------------------------------------------------------

    public function test_show_retorna_jogador_por_id(): void
    {
        $team   = Team::factory()->create();
        $player = Player::factory()->create(['team_id' => $team->id]);

        $response = $this->actingAsUser()->getJson("/api/v1/player/{$player->id}");

        $response->assertStatus(200)->assertJsonPath('data.id', $player->id);
    }

    // -------------------------------------------------------
    // PATCH /api/v1/player/{id}
    // -------------------------------------------------------

    public function test_update_altera_nome_do_jogador(): void
    {
        $team   = Team::factory()->create();
        $player = Player::factory()->create(['team_id' => $team->id, 'name' => 'Antigo']);

        $response = $this->actingAsUser()
            ->patchJson("/api/v1/player/{$player->id}", ['name' => 'Novo Nome']);

        $response->assertStatus(200)->assertJsonPath('data.name', 'Novo Nome');
    }

    // -------------------------------------------------------
    // DELETE /api/v1/player/{id}
    // -------------------------------------------------------

    public function test_destroy_deleta_jogador(): void
    {
        $team   = Team::factory()->create();
        $player = Player::factory()->create(['team_id' => $team->id]);

        $response = $this->actingAsUser()->deleteJson("/api/v1/player/{$player->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    }
}
