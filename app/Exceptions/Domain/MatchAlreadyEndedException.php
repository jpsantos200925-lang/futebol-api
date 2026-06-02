<?php

namespace App\Exceptions\Domain;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class MatchAlreadyEndedException extends RuntimeException
{
    public function __construct(int $matchId)
    {
        parent::__construct("Match #{$matchId} já foi encerrada.");
    }

    public function render(): JsonResponse
    {
        return response()->json(['data' => [], 'message' => $this->getMessage()], 404);
    }
}
