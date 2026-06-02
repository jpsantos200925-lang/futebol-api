<?php

namespace Tests\Unit\Services\ChampionshipMatch;

use App\Repositories\ChampionshipMatchsRepository;
use App\Services\ChampionshipMatch\DestroyChampionshipMatchService;
use App\Services\ChampionshipMatch\IndexChampionshipMatchService;
use App\Services\ChampionshipMatch\ShowChampionshipMatchService;
use App\Services\ChampionshipMatch\StoreChampionshipMatchService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ChampionshipMatchServiceTest extends TestCase
{
    private MockInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = Mockery::mock(ChampionshipMatchsRepository::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_retorna_todas_as_partidas(): void
    {
        $matches = Collection::make([(object)['id' => 1], (object)['id' => 2]]);
        $this->repo->shouldReceive('all')->once()->andReturn($matches);

        [$data, $message, $status] = (new IndexChampionshipMatchService($this->repo))->execute();

        $this->assertEquals(200, $status);
        $this->assertCount(2, $data);
    }

    public function test_show_retorna_partida_por_id(): void
    {
        $match = (object) ['id' => 1, 'away_team_id' => 2, 'home_team_id' => 3];
        $this->repo->shouldReceive('find')->with('1')->once()->andReturn($match);

        [$data, $message, $status] = (new ShowChampionshipMatchService($this->repo))->execute('1');

        $this->assertEquals(200, $status);
        $this->assertEquals(1, $data->id);
    }

    public function test_store_cria_partida_com_is_ended_false_por_padrao(): void
    {
        $input   = ['date' => '2025-01-15', 'start_time' => '15:00', 'away_team_id' => 1, 'home_team_id' => 2];
        $created = (object) array_merge($input, ['id' => 1, 'is_ended' => false]);

        $this->repo->shouldReceive('create')->once()->with($input)->andReturn($created);

        [$data, $message, $status] = (new StoreChampionshipMatchService($this->repo))->execute($input);

        $this->assertEquals(201, $status);
        $this->assertFalse($data->is_ended);
    }

    public function test_destroy_deleta_partida_por_id(): void
    {
        $match = (object) ['id' => 1];
        $this->repo->shouldReceive('find')->with('1')->once()->andReturn($match);
        $this->repo->shouldReceive('delete')->with('1')->once()->andReturn(true);

        [$data, $message, $status] = (new DestroyChampionshipMatchService($this->repo))->execute('1');

        $this->assertEquals(200, $status);
    }
}
