<?php

namespace Tests\Unit\Listeners;

use App\Events\EndOfTheMatch;
use App\Listeners\UpdateChampionshipTable;
use App\Services\Championship\UpdateChampionshipTableService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UpdateChampionshipTableTest extends TestCase
{
    protected function tearDown(): void
    {
        if ($container = Mockery::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }
        Mockery::close();
        parent::tearDown();
    }

    public function test_listener_chama_service_com_id_correto(): void
    {
        /** @var MockInterface $service */
        $service = Mockery::mock(UpdateChampionshipTableService::class);
        $service->shouldReceive('execute')->once()->with(42);

        $listener = new UpdateChampionshipTable($service);
        $listener->handle(new EndOfTheMatch(42));
    }
}
