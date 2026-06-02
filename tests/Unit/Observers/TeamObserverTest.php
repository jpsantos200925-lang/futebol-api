<?php

namespace Tests\Unit\Observers;

use App\Models\Team;
use App\Observers\TeamObserver;
use App\Repositories\ChampionshipMatchsRepository;
use App\Repositories\ChampionshipRepository;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TeamObserverTest extends TestCase
{
    private MockInterface $championshipRepo;
    private MockInterface $matchRepo;
    private TeamObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->championshipRepo = Mockery::mock(ChampionshipRepository::class);
        $this->matchRepo        = Mockery::mock(ChampionshipMatchsRepository::class);
        $this->observer         = new TeamObserver($this->championshipRepo, $this->matchRepo);
    }

    protected function tearDown(): void
    {
        if ($container = Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }
        Mockery::close();
        parent::tearDown();
    }

    private function makeTeam(int $id = 1): Team
    {
        $team     = new Team(['name' => 'Flamengo']);
        $team->id = $id;
        return $team;
    }

    public function test_cria_championship_ao_criar_time(): void
    {
        $team = $this->makeTeam(5);

        $this->championshipRepo->shouldReceive('create')
            ->once()
            ->with(['team_id' => 5]);

        $this->observer->created($team);
    }

    public function test_deleta_partidas_ao_deletar_time(): void
    {
        $team = $this->makeTeam(5);

        $this->matchRepo->shouldReceive('deleteByTeamId')->once()->with(5);

        $championship = (object) ['id' => 10];
        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 5])
            ->andReturn(collect([$championship]));

        $this->championshipRepo->shouldReceive('delete')->once()->with(10);

        $this->observer->deleted($team);
    }

    public function test_deleta_championship_ao_deletar_time(): void
    {
        $team = $this->makeTeam(5);

        $this->matchRepo->shouldReceive('deleteByTeamId')->once()->withAnyArgs();

        $championship = (object) ['id' => 42];
        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 5])
            ->andReturn(collect([$championship]));

        $this->championshipRepo->shouldReceive('delete')->once()->with(42);

        $this->observer->deleted($team);
    }

    public function test_nao_deleta_championship_se_nao_existir(): void
    {
        $team = $this->makeTeam(5);

        $this->matchRepo->shouldReceive('deleteByTeamId')->once()->withAnyArgs();

        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 5])
            ->andReturn(collect([]));

        $this->championshipRepo->shouldNotReceive('delete');

        $this->observer->deleted($team);
    }

    public function test_recria_championship_ao_restaurar_time(): void
    {
        $team = $this->makeTeam(5);

        $this->championshipRepo->shouldReceive('create')
            ->once()
            ->with(['team_id' => 5]);

        $this->observer->restored($team);
    }
}
