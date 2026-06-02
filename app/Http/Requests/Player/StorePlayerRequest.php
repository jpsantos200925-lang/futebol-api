<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class StorePlayerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (!isset($data[0]) || !is_array($data[0])) {
            $this->replace([$data]);
        }
    }

    public function rules(): array
    {
        return [
            '*'          => ['array'],
            '*.name'     => ['required', 'string', 'max:150'],
            '*.number'   => ['required', 'integer', 'min:1', 'max:99'],
            '*.team_id'  => ['required', 'integer', 'exists:teams,id'],
        ];
    }
}
