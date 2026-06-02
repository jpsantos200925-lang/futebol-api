<?php

namespace Tests\Unit\Repositories;

use App\Models\Team;
use App\Repositories\TeamRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private TeamRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = app(TeamRepository::class);
    }

    public function test_all_retorna_collection_de_registros(): void
    {
        Team::factory()->count(3)->create();

        $result = $this->repo->all();

        $this->assertCount(3, $result);
    }

    public function test_find_retorna_registro_por_id(): void
    {
        $team = Team::factory()->create(['name' => 'Flamengo']);

        $found = $this->repo->find($team->id);

        $this->assertNotNull($found);
        $this->assertEquals('Flamengo', $found->name);
    }

    public function test_find_retorna_null_para_id_inexistente(): void
    {
        $result = $this->repo->find(99999);

        $this->assertNull($result);
    }

    public function test_create_persiste_registro(): void
    {
        $team = $this->repo->create(['name' => 'Palmeiras']);

        $this->assertNotNull($team->id);
        $this->assertDatabaseHas('teams', ['name' => 'Palmeiras']);
    }

    public function test_update_altera_campos_do_registro(): void
    {
        $team = Team::factory()->create(['name' => 'Antigo']);

        $this->repo->update(['name' => 'Novo'], $team->id);

        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'Novo']);
    }

    public function test_delete_remove_registro(): void
    {
        $team = Team::factory()->create();

        $this->repo->delete($team->id);

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    public function test_all_com_search_filtra_por_campo_searchable(): void
    {
        Team::factory()->create(['name' => 'Flamengo']);
        Team::factory()->create(['name' => 'Palmeiras']);

        $result = $this->repo->all(['name' => 'Flamengo']);

        $this->assertCount(1, $result);
        $this->assertEquals('Flamengo', $result->first()->name);
    }

    public function test_all_com_limit_respeita_quantidade(): void
    {
        Team::factory()->count(5)->create();

        $result = $this->repo->all([], 0, 2);

        $this->assertCount(2, $result);
    }

    public function test_all_com_skip_pula_registros(): void
    {
        // MySQL exige LIMIT quando há OFFSET — sempre passamos limit junto com skip
        Team::factory()->count(4)->create();

        $result = $this->repo->all([], 2, 10);

        $this->assertCount(2, $result);
    }

    public function test_exists_retorna_true_quando_ha_registros(): void
    {
        Team::factory()->create();

        $this->assertTrue($this->repo->exists());
    }

    public function test_exists_retorna_false_quando_vazio(): void
    {
        $this->assertFalse($this->repo->exists());
    }

    public function test_first_retorna_primeiro_registro(): void
    {
        $first  = Team::factory()->create(['name' => 'Primeiro']);
        Team::factory()->create(['name' => 'Segundo']);

        $result = $this->repo->first();

        $this->assertEquals($first->id, $result->id);
    }

    public function test_paginate_retorna_estrutura_correta(): void
    {
        Team::factory()->count(5)->create();

        $result = $this->repo->paginate(2);

        $this->assertEquals(2, $result->perPage());
        $this->assertEquals(5, $result->total());
    }
}
