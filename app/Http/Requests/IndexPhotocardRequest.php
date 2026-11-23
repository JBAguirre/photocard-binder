<?php

namespace App\Http\Requests;

use App\Enums\CardType;
use App\Enums\PhotocardStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPhotocardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(PhotocardStatus::class)],
            'card_type' => ['nullable', Rule::enum(CardType::class)],
            'group' => ['nullable', 'string', 'max:100'],
            'member' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }
}
