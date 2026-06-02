<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['sometimes', 'string', 'max:150'],
            'number'  => ['sometimes', 'integer', 'min:1', 'max:99'],
            'team_id' => ['sometimes', 'integer', 'exists:teams,id'],
        ];
    }
}
