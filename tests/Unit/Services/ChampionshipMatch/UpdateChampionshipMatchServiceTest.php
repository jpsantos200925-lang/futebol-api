<?php

namespace Tests\Unit\Services\ChampionshipMatch;

use App\Events\EndOfTheMatch;
use App\Repositories\ChampionshipMatchsRepository;
use App\Services\ChampionshipMatch\UpdateChampionshipMatchService;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UpdateChampionshipMatchServiceTest extends TestCase
{
    private MockInterface $matchRepo;
    private UpdateChampionshipMatchService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->matchRepo = Mockery::mock(ChampionshipMatchsRepository::class);
        $this->service   = new UpdateChampionshipMatchService($this->matchRepo);
    }

    protected function tearDown(): void
    {
        if ($container = Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }
        Mockery::close();
        parent::tearDown();
    }

    private function makeMatch(bool $isEnded = false): object
    {
        $match = Mockery::mock();
        $match->is_ended = $isEnded;
        $match->shouldReceive('refresh')->andReturnSelf();
        return $match;
    }

    public function test_atualiza_gols_de_partida_em_andamento(): void
    {
        $match = $this->makeMatch(false);

        $this->matchRepo->shouldReceive('find')->with('1')->andReturn($match);
        $this->matchRepo->shouldReceive('update')
            ->once()
            ->with(Mockery::on(fn($d) => $d['away_team_goals'] === 2 && $d['home_team_goals'] === 1), '1');

        [$data, $message, $status] = $this->service->execute(['away_team_goals' => 2, 'home_team_goals' => 1], '1');

        $this->assertEquals(200, $status);
        $this->assertEquals('Match in Championship updated successfully.', $message);
    }

    public function test_define_end_time_automaticamente_ao_finalizar(): void
    {
        Event::fake();

        $match = $this->makeMatch(false);

        $this->matchRepo->shouldReceive('find')->with('1')->andReturn($match);
        $this->matchRepo->shouldReceive('update')
            ->once()
            ->with(Mockery::on(fn($d) => isset($d['end_time'])), '1');

        $this->service->execute(['is_ended' => true], '1');
    }

    public function test_dispara_evento_end_of_the_match_ao_finalizar(): void
    {
        Event::fake();

        $match = $this->makeMatch(false);

        $this->matchRepo->shouldReceive('find')->with('1')->andReturn($match);
        $this->matchRepo->shouldReceive('update')->once()->withAnyArgs();

        $this->service->execute(['is_ended' => true], '1');

        // championship_match_id é cast para int pelo tipo da propriedade
        Event::assertDispatched(EndOfTheMatch::class, fn($e) => $e->championship_match_id == 1);
    }

    public function test_nao_dispara_evento_se_is_ended_false(): void
    {
        Event::fake();

        $match = $this->makeMatch(false);

        $this->matchRepo->shouldReceive('find')->with('1')->andReturn($match);
        $this->matchRepo->shouldReceive('update')->once()->withAnyArgs();

        $this->service->execute(['away_team_goals' => 1], '1');

        Event::assertNotDispatched(EndOfTheMatch::class);
    }

    public function test_impede_atualizacao_de_partida_ja_finalizada(): void
    {
        $match = $this->makeMatch(true);

        $this->matchRepo->shouldReceive('find')->with('1')->andReturn($match);
        $this->matchRepo->shouldNotReceive('update');

        $this->expectException(\App\Exceptions\Domain\MatchAlreadyEndedException::class);

        $this->service->execute(['away_team_goals' => 3], '1');
    }

    public function test_retorna_erro_para_partida_inexistente(): void
    {
        $this->matchRepo->shouldReceive('find')->with('99')->andReturn(null);

        [$data, $message, $status] = $this->service->execute(['away_team_goals' => 1], '99');

        $this->assertEquals(404, $status);
        $this->assertEmpty($data);
    }

    public function test_retorna_erro_para_id_nao_numerico(): void
    {
        [$data, $message, $status] = $this->service->execute(['away_team_goals' => 1], 'abc');

        $this->assertEquals(404, $status);
        $this->assertEquals('Invalid ID.', $message);
    }
}
