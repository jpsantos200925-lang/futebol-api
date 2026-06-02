<?php

namespace Tests\Unit\Services\Championship;

use App\Repositories\ChampionshipRepository;
use App\Services\Championship\DestroyChampionshipService;
use App\Services\Championship\IndexChampionshipService;
use App\Services\Championship\ShowChampionshipService;
use App\Services\Championship\StoreChampionshipService;
use App\Services\Championship\UpdateChampionshipService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ChampionshipServiceTest extends TestCase
{
    private MockInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = Mockery::mock(ChampionshipRepository::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_retorna_todos_os_championships(): void
    {
        $championships = Collection::make([(object)['id' => 1], (object)['id' => 2]]);
        $this->repo->shouldReceive('all')->once()->andReturn($championships);

        [$data, $message, $status] = (new IndexChampionshipService($this->repo))->execute();

        $this->assertEquals(200, $status);
        $this->assertCount(2, $data);
    }

    public function test_show_retorna_championship_por_id(): void
    {
        $championship = (object) ['id' => 1, 'team_id' => 5, 'points' => 9];
        $this->repo->shouldReceive('find')->with('1')->once()->andReturn($championship);

        [$data, $message, $status] = (new ShowChampionshipService($this->repo))->execute('1');

        $this->assertEquals(200, $status);
        $this->assertEquals(9, $data->points);
    }

    public function test_show_retorna_404_para_id_inexistente(): void
    {
        $this->repo->shouldReceive('find')->with('99')->once()->andReturn(null);

        [$data, $message, $status] = (new ShowChampionshipService($this->repo))->execute('99');

        $this->assertEquals(404, $status);
    }

    public function test_store_cria_championship_com_team_id(): void
    {
        $created = (object) ['id' => 1, 'team_id' => 5, 'points' => 0];
        $this->repo->shouldReceive('create')
            ->once()
            ->with(['team_id' => 5])
            ->andReturn($created);

        [$data, $message, $status] = (new StoreChampionshipService($this->repo))->execute(['team_id' => 5]);

        $this->assertEquals(201, $status);
        $this->assertEquals(5, $data->team_id);
    }

    public function test_update_atualiza_campos_do_championship(): void
    {
        $existing = (object) ['id' => 1, 'points' => 3];
        $updated  = Mockery::mock();
        $updated->shouldReceive('refresh')->andReturnSelf();
        $updated->points = 6;

        $this->repo->shouldReceive('find')->with('1')->once()->andReturn($existing);
        $this->repo->shouldReceive('update')
            ->once()
            ->with(['points' => 6], '1')
            ->andReturn($updated);

        [$data, $message, $status] = (new UpdateChampionshipService($this->repo))->execute(['points' => 6], '1');

        $this->assertEquals(200, $status);
    }

    public function test_destroy_deleta_championship_por_id(): void
    {
        $championship = (object) ['id' => 1];
        $this->repo->shouldReceive('find')->with('1')->once()->andReturn($championship);
        $this->repo->shouldReceive('delete')->with('1')->once()->andReturn(true);

        [$data, $message, $status] = (new DestroyChampionshipService($this->repo))->execute('1');

        $this->assertEquals(200, $status);
    }
}
