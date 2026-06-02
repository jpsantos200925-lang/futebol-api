<?php

namespace Tests\Feature\Http;

use App\Models\Championship;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------
    // GET /api/v1/team
    // -------------------------------------------------------

    public function test_index_retorna_lista_de_times_autenticado(): void
    {
        Team::factory()->count(3)->create();

        $response = $this->actingAsUser()
            ->getJson('/api/v1/team');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_index_retorna_401_sem_autenticacao(): void
    {
        $this->getJson('/api/v1/team')->assertStatus(401);
    }

    // -------------------------------------------------------
    // POST /api/v1/team
    // -------------------------------------------------------

    public function test_store_cria_time_e_championship_automaticamente(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/v1/team', ['name' => 'Flamengo']);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Flamengo');

        $this->assertDatabaseHas('teams', ['name' => 'Flamengo']);

        $team = Team::where('name', 'Flamengo')->first();
        $this->assertDatabaseHas('championships', ['team_id' => $team->id]);
    }

    public function test_store_retorna_422_para_nome_duplicado(): void
    {
        Team::factory()->create(['name' => 'Palmeiras']);

        $response = $this->actingAsUser()
            ->postJson('/api/v1/team', ['name' => 'Palmeiras']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_store_retorna_422_sem_nome(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/v1/team', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_store_retorna_401_sem_autenticacao(): void
    {
        $this->postJson('/api/v1/team', ['name' => 'Flamengo'])->assertStatus(401);
    }

    // -------------------------------------------------------
    // GET /api/v1/team/{id}
    // -------------------------------------------------------

    public function test_show_retorna_time_existente(): void
    {
        $team = Team::factory()->create(['name' => 'Corinthians']);

        $response = $this->actingAsUser()
            ->getJson("/api/v1/team/{$team->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Corinthians');
    }

    public function test_show_retorna_erro_para_id_inexistente(): void
    {
        $response = $this->actingAsUser()
            ->getJson('/api/v1/team/99999');

        $response->assertStatus(404);
    }

    // -------------------------------------------------------
    // PATCH /api/v1/team/{id}
    // -------------------------------------------------------

    public function test_update_altera_nome_do_time(): void
    {
        $team = Team::factory()->create(['name' => 'Antigo']);

        $response = $this->actingAsUser()
            ->patchJson("/api/v1/team/{$team->id}", ['name' => 'Novo']);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Novo');

        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'Novo']);
    }

    public function test_update_retorna_422_para_nome_ja_usado_em_outro_time(): void
    {
        Team::factory()->create(['name' => 'Reservado']);
        $team = Team::factory()->create(['name' => 'Original']);

        $response = $this->actingAsUser()
            ->patchJson("/api/v1/team/{$team->id}", ['name' => 'Reservado']);

        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    // -------------------------------------------------------
    // DELETE /api/v1/team/{id}
    // -------------------------------------------------------

    public function test_destroy_deleta_time(): void
    {
        $team = Team::factory()->create();

        $response = $this->actingAsUser()
            ->deleteJson("/api/v1/team/{$team->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_destroy_remove_jogadores_e_championship_em_cascata(): void
    {
        $team = Team::factory()->create();
        Player::factory()->count(2)->create(['team_id' => $team->id]);

        $this->actingAsUser()->deleteJson("/api/v1/team/{$team->id}");

        $this->assertDatabaseMissing('players', ['team_id' => $team->id]);
        $this->assertDatabaseMissing('championships', ['team_id' => $team->id]);
    }

    public function test_destroy_retorna_401_sem_autenticacao(): void
    {
        $team = Team::factory()->create();
        $this->deleteJson("/api/v1/team/{$team->id}")->assertStatus(401);
    }
}
