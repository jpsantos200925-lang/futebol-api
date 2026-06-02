<?php

namespace Tests\Unit\Services\Championship;

use App\Repositories\ChampionshipMatchsRepository;
use App\Repositories\ChampionshipRepository;
use App\Services\Championship\UpdateChampionshipTableService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UpdateChampionshipTableServiceTest extends TestCase
{
    private MockInterface $matchRepo;
    private MockInterface $championshipRepo;
    private UpdateChampionshipTableService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->matchRepo        = Mockery::mock(ChampionshipMatchsRepository::class);
        $this->championshipRepo = Mockery::mock(ChampionshipRepository::class);

        $this->service = new UpdateChampionshipTableService(
            $this->matchRepo,
            $this->championshipRepo
        );
    }

    protected function tearDown(): void
    {
        if ($container = Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }
        Mockery::close();
        parent::tearDown();
    }

    private function makeMatch(int $awayId, int $homeId, int $awayGoals, int $homeGoals): object
    {
        return (object) [
            'away_team_id'    => $awayId,
            'home_team_id'    => $homeId,
            'away_team_goals' => $awayGoals,
            'home_team_goals' => $homeGoals,
        ];
    }

    private function makeChampionship(int $id, int $teamId, int $points = 0, int $goals = 0, int $victories = 0, int $defeats = 0, int $draws = 0): object
    {
        return (object) [
            'id'                  => $id,
            'team_id'             => $teamId,
            'points'              => $points,
            'number_of_goals'     => $goals,
            'number_of_victories' => $victories,
            'number_of_defeats'   => $defeats,
            'number_of_draws'     => $draws,
        ];
    }

    public function test_time_visitante_vence_recebe_3_pontos(): void
    {
        $this->matchRepo->shouldReceive('find')->with(1)->andReturn(
            $this->makeMatch(awayId: 10, homeId: 20, awayGoals: 2, homeGoals: 0)
        );

        $awayChampionship = $this->makeChampionship(id: 1, teamId: 10);
        $homeChampionship = $this->makeChampionship(id: 2, teamId: 20);

        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 10])->andReturn(collect([$awayChampionship]));
        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 20])->andReturn(collect([$homeChampionship]));

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($data) => $data['points'] === 3 && $data['number_of_victories'] === 1 && $data['number_of_defeats'] === 0),
                1
            );

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($data) => $data['points'] === 0 && $data['number_of_victories'] === 0 && $data['number_of_defeats'] === 1),
                2
            );

        $this->service->execute(1);
    }

    public function test_time_mandante_vence_recebe_3_pontos(): void
    {
        $this->matchRepo->shouldReceive('find')->with(1)->andReturn(
            $this->makeMatch(awayId: 10, homeId: 20, awayGoals: 0, homeGoals: 3)
        );

        $awayChampionship = $this->makeChampionship(id: 1, teamId: 10);
        $homeChampionship = $this->makeChampionship(id: 2, teamId: 20);

        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 10])->andReturn(collect([$awayChampionship]));
        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 20])->andReturn(collect([$homeChampionship]));

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($data) => $data['points'] === 0 && $data['number_of_victories'] === 0 && $data['number_of_defeats'] === 1),
                1
            );

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($data) => $data['points'] === 3 && $data['number_of_victories'] === 1 && $data['number_of_defeats'] === 0),
                2
            );

        $this->service->execute(1);
    }

    public function test_empate_ambos_recebem_1_ponto(): void
    {
        $this->matchRepo->shouldReceive('find')->with(1)->andReturn(
            $this->makeMatch(awayId: 10, homeId: 20, awayGoals: 1, homeGoals: 1)
        );

        $awayChampionship = $this->makeChampionship(id: 1, teamId: 10);
        $homeChampionship = $this->makeChampionship(id: 2, teamId: 20);

        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 10])->andReturn(collect([$awayChampionship]));
        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 20])->andReturn(collect([$homeChampionship]));

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($data) => $data['points'] === 1 && $data['number_of_victories'] === 0 && $data['number_of_defeats'] === 0),
                1
            );

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($data) => $data['points'] === 1 && $data['number_of_victories'] === 0 && $data['number_of_defeats'] === 0),
                2
            );

        $this->service->execute(1);
    }

    public function test_gols_sao_acumulados_corretamente(): void
    {
        $this->matchRepo->shouldReceive('find')->with(1)->andReturn(
            $this->makeMatch(awayId: 10, homeId: 20, awayGoals: 3, homeGoals: 1)
        );

        $awayChampionship = $this->makeChampionship(id: 1, teamId: 10, goals: 5);
        $homeChampionship = $this->makeChampionship(id: 2, teamId: 20, goals: 2);

        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 10])->andReturn(collect([$awayChampionship]));
        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 20])->andReturn(collect([$homeChampionship]));

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(Mockery::on(fn($data) => $data['number_of_goals'] === 8), 1);

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(Mockery::on(fn($data) => $data['number_of_goals'] === 3), 2);

        $this->service->execute(1);
    }

    public function test_pontos_acumulam_sobre_partidas_anteriores(): void
    {
        $this->matchRepo->shouldReceive('find')->with(1)->andReturn(
            $this->makeMatch(awayId: 10, homeId: 20, awayGoals: 2, homeGoals: 0)
        );

        $awayChampionship = $this->makeChampionship(id: 1, teamId: 10, points: 6, victories: 2);
        $homeChampionship = $this->makeChampionship(id: 2, teamId: 20, points: 3, victories: 1);

        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 10])->andReturn(collect([$awayChampionship]));
        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 20])->andReturn(collect([$homeChampionship]));

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($data) => $data['points'] === 9 && $data['number_of_victories'] === 3),
                1
            );

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($data) => $data['points'] === 3 && $data['number_of_defeats'] === 1),
                2
            );

        $this->service->execute(1);
    }

    public function test_vitorias_acumulam_em_sequencia(): void
    {
        // 3ª vitória do time visitante
        $this->matchRepo->shouldReceive('find')->with(1)->andReturn(
            $this->makeMatch(awayId: 10, homeId: 20, awayGoals: 1, homeGoals: 0)
        );

        $awayChampionship = $this->makeChampionship(id: 1, teamId: 10, victories: 2);
        $homeChampionship = $this->makeChampionship(id: 2, teamId: 20);

        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 10])->andReturn(collect([$awayChampionship]));
        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 20])->andReturn(collect([$homeChampionship]));

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(Mockery::on(fn($data) => $data['number_of_victories'] === 3), 1);

        $this->championshipRepo->shouldReceive('update')->once()->withAnyArgs();

        $this->service->execute(1);
    }

    public function test_derrotas_acumulam_em_sequencia(): void
    {
        // 2ª derrota do time mandante
        $this->matchRepo->shouldReceive('find')->with(1)->andReturn(
            $this->makeMatch(awayId: 10, homeId: 20, awayGoals: 2, homeGoals: 0)
        );

        $awayChampionship = $this->makeChampionship(id: 1, teamId: 10);
        $homeChampionship = $this->makeChampionship(id: 2, teamId: 20, defeats: 1);

        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 10])->andReturn(collect([$awayChampionship]));
        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 20])->andReturn(collect([$homeChampionship]));

        $this->championshipRepo->shouldReceive('update')->once()->withAnyArgs();

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(Mockery::on(fn($data) => $data['number_of_defeats'] === 2), 2);

        $this->service->execute(1);
    }

    public function test_empate_nao_incrementa_vitorias_nem_derrotas(): void
    {
        $this->matchRepo->shouldReceive('find')->with(1)->andReturn(
            $this->makeMatch(awayId: 10, homeId: 20, awayGoals: 0, homeGoals: 0)
        );

        $awayChampionship = $this->makeChampionship(id: 1, teamId: 10, victories: 1, defeats: 1);
        $homeChampionship = $this->makeChampionship(id: 2, teamId: 20, victories: 1, defeats: 1);

        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 10])->andReturn(collect([$awayChampionship]));
        $this->championshipRepo->shouldReceive('allQuery')
            ->with(['team_id' => 20])->andReturn(collect([$homeChampionship]));

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($data) => $data['number_of_victories'] === 1 && $data['number_of_defeats'] === 1),
                1
            );

        $this->championshipRepo->shouldReceive('update')
            ->once()
            ->with(
                Mockery::on(fn($data) => $data['number_of_victories'] === 1 && $data['number_of_defeats'] === 1),
                2
            );

        $this->service->execute(1);
    }
}
