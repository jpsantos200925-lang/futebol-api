<?php

namespace Tests\Feature\Flows;

use App\Models\Championship;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamObserverFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_championship_criado_automaticamente_ao_criar_time(): void
    {
        $response = $this->actingAsUser()
            ->postJson('/api/v1/team', ['name' => 'Santos FC']);

        $response->assertStatus(201);

        $team = Team::where('name', 'Santos FC')->first();
        $this->assertNotNull($team);

        $this->assertDatabaseHas('championships', ['team_id' => $team->id]);

        $championship = Championship::where('team_id', $team->id)->first();
        $this->assertEquals(0, $championship->points);
        $this->assertEquals(0, $championship->number_of_goals);
        $this->assertEquals(0, $championship->number_of_victories);
        $this->assertEquals(0, $championship->number_of_defeats);
    }

    public function test_championship_deletado_ao_deletar_time(): void
    {
        $team = Team::factory()->create();

        $this->assertDatabaseHas('championships', ['team_id' => $team->id]);

        $this->actingAsUser()->deleteJson("/api/v1/team/{$team->id}");

        $this->assertDatabaseMissing('championships', ['team_id' => $team->id]);
    }

    public function test_valores_iniciais_do_championship_sao_zero(): void
    {
        $team         = Team::factory()->create();
        $championship = Championship::where('team_id', $team->id)->first();

        $this->assertNotNull($championship);
        $this->assertSame(0, $championship->points);
        $this->assertSame(0, $championship->number_of_goals);
        $this->assertSame(0, $championship->number_of_victories);
        $this->assertSame(0, $championship->number_of_defeats);
    }
}
