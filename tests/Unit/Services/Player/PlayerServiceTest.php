<?php

namespace Tests\Unit\Services\Player;

use App\Repositories\PlayerRepository;
use App\Repositories\TeamRepository;
use App\Services\Player\DestroyPlayerService;
use App\Services\Player\IndexPlayerService;
use App\Services\Player\ShowPlayerService;
use App\Services\Player\StorePlayerService;
use App\Services\Player\UpdatePlayerService;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PlayerServiceTest extends TestCase
{
    private MockInterface $playerRepo;
    private MockInterface $teamRepo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->playerRepo = Mockery::mock(PlayerRepository::class);
        $this->teamRepo   = Mockery::mock(TeamRepository::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeStore(): StorePlayerService
    {
        return new StorePlayerService($this->playerRepo, $this->teamRepo);
    }

    // -------------------------------------------------------
    // StorePlayerService
    // -------------------------------------------------------

    public function test_cria_jogador_com_numero_unico_no_time(): void
    {
        $this->playerRepo->shouldReceive('allQuery')
            ->with(['number' => 10, 'team_id' => 1])
            ->andReturn(collect([]));

        $team = (object) ['name' => 'Flamengo'];
        $this->teamRepo->shouldReceive('find')->with(1)->andReturn($team);

        $createdPlayer = (object) ['id' => 1, 'name' => 'João', 'number' => 10, 'team_id' => 1];
        $this->playerRepo->shouldReceive('create')
            ->with(['name' => 'João', 'number' => 10, 'team_id' => 1])
            ->andReturn($createdPlayer);

        [$data, $message, $status] = $this->makeStore()->execute([
            ['name' => 'João', 'number' => 10, 'team_id' => 1],
        ]);

        $this->assertEquals(201, $status);
        $this->assertCount(1, $data);
    }

    public function test_rejeita_numero_de_camisa_duplicado_no_mesmo_time(): void
    {
        $existingPlayer = (object) ['id' => 5, 'number' => 10, 'team_id' => 1];
        $this->playerRepo->shouldReceive('allQuery')
            ->with(['number' => 10, 'team_id' => 1])
            ->andReturn(collect([$existingPlayer]));

        $team = (object) ['name' => 'Flamengo'];
        $this->teamRepo->shouldReceive('find')->with(1)->andReturn($team);

        $this->expectException(\App\Exceptions\Domain\ShirtNumberAlreadyInUseException::class);
        $this->expectExceptionMessageMatches('/10/');
        $this->expectExceptionMessageMatches('/Flamengo/');

        $this->makeStore()->execute([
            ['name' => 'Pedro', 'number' => 10, 'team_id' => 1],
        ]);
    }

    public function test_permite_mesmo_numero_em_times_diferentes(): void
    {
        // Número 10 no time 2 (não existe ainda)
        $this->playerRepo->shouldReceive('allQuery')
            ->with(['number' => 10, 'team_id' => 2])
            ->andReturn(collect([]));

        $team = (object) ['name' => 'Palmeiras'];
        $this->teamRepo->shouldReceive('find')->with(2)->andReturn($team);

        $createdPlayer = (object) ['id' => 2, 'name' => 'Carlos', 'number' => 10, 'team_id' => 2];
        $this->playerRepo->shouldReceive('create')->andReturn($createdPlayer);

        [$data, $message, $status] = $this->makeStore()->execute([
            ['name' => 'Carlos', 'number' => 10, 'team_id' => 2],
        ]);

        $this->assertEquals(201, $status);
    }

    // -------------------------------------------------------
    // IndexPlayerService
    // -------------------------------------------------------

    public function test_index_retorna_todos_os_jogadores(): void
    {
        $players = Collection::make([(object)['id' => 1], (object)['id' => 2]]);
        $this->playerRepo->shouldReceive('all')->andReturn($players);

        $service = new IndexPlayerService($this->playerRepo);
        [$data, $message, $status] = $service->execute();

        $this->assertEquals(200, $status);
        $this->assertCount(2, $data);
    }

    // -------------------------------------------------------
    // ShowPlayerService
    // -------------------------------------------------------

    public function test_show_retorna_jogador_por_id(): void
    {
        $player = (object) ['id' => 1, 'name' => 'João'];
        $this->playerRepo->shouldReceive('find')->with('1')->andReturn($player);

        $service = new ShowPlayerService($this->playerRepo);
        [$data, $message, $status] = $service->execute('1');

        $this->assertEquals(200, $status);
        $this->assertEquals('João', $data->name);
    }

    // -------------------------------------------------------
    // UpdatePlayerService
    // -------------------------------------------------------

    public function test_update_atualiza_dados_do_jogador(): void
    {
        $existingPlayer = Mockery::mock();
        $existingPlayer->id = 1;

        $updatedPlayer = Mockery::mock();
        $updatedPlayer->name = 'Novo Nome';
        $updatedPlayer->shouldReceive('refresh')->andReturnSelf();

        $this->playerRepo->shouldReceive('find')->with('1')->andReturn($existingPlayer);
        $this->playerRepo->shouldReceive('update')
            ->with(['name' => 'Novo Nome'], '1')
            ->andReturn($updatedPlayer);

        $service = new UpdatePlayerService($this->playerRepo);
        [$data, $message, $status] = $service->execute(['name' => 'Novo Nome'], '1');

        $this->assertEquals(200, $status);
        $this->assertEquals('Novo Nome', $data->name);
    }

    // -------------------------------------------------------
    // DestroyPlayerService
    // -------------------------------------------------------

    public function test_destroy_deleta_jogador(): void
    {
        $player = (object) ['id' => 1, 'name' => 'João'];

        $this->playerRepo->shouldReceive('find')->with('1')->andReturn($player);
        $this->playerRepo->shouldReceive('delete')->with('1')->andReturn(true);

        $service = new DestroyPlayerService($this->playerRepo);
        [$data, $message, $status] = $service->execute('1');

        $this->assertEquals(200, $status);
    }
}
