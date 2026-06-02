<?php

namespace App\Http\Requests\ChampionshipMatch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChampionshipMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'away_team_goals' => ['sometimes', 'integer', 'min:0'],
            'home_team_goals' => ['sometimes', 'integer', 'min:0'],
            'is_ended'        => ['sometimes', 'boolean'],
        ];
    }
}
