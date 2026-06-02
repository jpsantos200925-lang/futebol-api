<?php

namespace Tests\Unit\Events;

use App\Events\EndOfTheMatch;
use Tests\TestCase;

class EndOfTheMatchTest extends TestCase
{
    public function test_evento_armazena_championship_match_id(): void
    {
        $event = new EndOfTheMatch(42);

        $this->assertEquals(42, $event->championship_match_id);
    }
}
