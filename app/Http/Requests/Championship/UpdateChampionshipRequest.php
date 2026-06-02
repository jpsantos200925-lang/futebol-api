<?php

namespace App\Http\Requests\Championship;

use Illuminate\Foundation\Http\FormRequest;

class UpdateChampionshipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'team_id'             => ['sometimes', 'integer', 'exists:teams,id'],
            'points'              => ['sometimes', 'integer', 'min:0'],
            'number_of_goals'     => ['sometimes', 'integer', 'min:0'],
            'number_of_victories' => ['sometimes', 'integer', 'min:0'],
            'number_of_defeats'   => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
