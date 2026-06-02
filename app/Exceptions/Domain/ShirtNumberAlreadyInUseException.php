<?php

namespace App\Exceptions\Domain;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class ShirtNumberAlreadyInUseException extends RuntimeException
{
    public function __construct(int $number, string $teamName)
    {
        parent::__construct("O número da camisa {$number} já está em uso no {$teamName}.");
    }

    public function render(): JsonResponse
    {
        return response()->json(['data' => [], 'message' => $this->getMessage()], 400);
    }
}
