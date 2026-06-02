<?php

namespace Tests\Unit\Services\Team;

use App\Models\Team;
use App\Repositories\TeamRepository;
use App\Services\Team\DestroyTeamService;
use App\Services\Team\IndexTeamService;
use App\Services\Team\ShowTeamService;
use App\Services\Team\StoreTeamService;
use App\Services\Team\UpdateTeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TeamServiceTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = Mockery::mock(TeamRepository::class);
    }

    protected function tearDown(): void
    {
        if ($container = Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_retorna_todos_os_times(): void
    {
        $teams = Collection::make([(object)['id' => 1], (object)['id' => 2]]);
        $this->repo->shouldReceive('all')->once()->andReturn($teams);

        [$data, $message, $status] = (new IndexTeamService($this->repo))->execute();

        $this->assertEquals(200, $status);
        $this->assertCount(2, $data);
    }

    public function test_store_cria_time_com_nome_valido(): void
    {
        $created = (object) ['id' => 1, 'name' => 'Flamengo'];
        $this->repo->shouldReceive('create')->once()->andReturn($created);

        [$data, $message, $status] = (new StoreTeamService($this->repo))->execute(['name' => 'Flamengo']);

        $this->assertEquals(201, $status);
        $this->assertEquals('Flamengo', $data->name);
    }

    public function test_show_retorna_time_por_id(): void
    {
        // ShowTeamService faz $team['players'] = $team->players antes de checar null,
        // então precisa de um modelo Eloquent real (não um stdClass)
        $team = Team::factory()->create(['name' => 'Flamengo']);

        $this->repo->shouldReceive('find')->with('1')->once()->andReturn($team);

        [$data, $message, $status] = (new ShowTeamService($this->repo))->execute('1');

        $this->assertEquals(200, $status);
    }

    public function test_update_atualiza_nome_do_time(): void
    {
        $existing = (object) ['id' => 1, 'name' => 'Antigo'];
        $updated  = Mockery::mock();
        $updated->shouldReceive('refresh')->andReturnSelf();
        $updated->name = 'Novo';

        $this->repo->shouldReceive('find')->with('1')->once()->andReturn($existing);
        $this->repo->shouldReceive('update')
            ->once()
            ->with(['name' => 'Novo'], '1')
            ->andReturn($updated);

        [$data, $message, $status] = (new UpdateTeamService($this->repo))->execute(['name' => 'Novo'], '1');

        $this->assertEquals(200, $status);
    }

    public function test_destroy_deleta_time(): void
    {
        $team = (object) ['id' => 1];
        $this->repo->shouldReceive('find')->with('1')->once()->andReturn($team);
        $this->repo->shouldReceive('delete')->with('1')->once()->andReturn(true);

        [$data, $message, $status] = (new DestroyTeamService($this->repo))->execute('1');

        $this->assertEquals(200, $status);
    }
}
