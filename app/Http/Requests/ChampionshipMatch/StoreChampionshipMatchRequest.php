<?php

namespace App\Http\Requests\ChampionshipMatch;

use Illuminate\Foundation\Http\FormRequest;

class StoreChampionshipMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'         => ['required', 'date_format:Y-m-d'],
            'start_time'   => ['required', 'date_format:H:i'],
            'away_team_id' => ['required', 'integer', 'exists:teams,id', 'different:home_team_id'],
            'home_team_id' => ['required', 'integer', 'exists:teams,id', 'different:away_team_id'],
        ];
    }
}
