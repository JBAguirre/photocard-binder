<?php

namespace App\Http\Requests;

use App\Enums\CardCondition;
use App\Enums\CardType;
use App\Enums\PhotocardStatus;
use App\Models\Photocard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePhotocardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_name' => ['required', 'string', 'max:100'],
            'member_name' => ['required', 'string', 'max:100'],
            'album' => ['nullable', 'string', 'max:150'],
            'version' => ['nullable', 'string', 'max:100'],
            'card_type' => ['required', Rule::enum(CardType::class)],
            'status' => ['required', Rule::enum(PhotocardStatus::class)],
            'condition' => ['nullable', Rule::enum(CardCondition::class)],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // Only check for duplicates once the basic rules have passed.
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->isDuplicate()) {
                $validator->errors()->add(
                    'group_name',
                    'You already have this card in your binder.'
                );
            }
        });
    }

    private function isDuplicate(): bool
    {
        return Photocard::query()
            ->where('user_id', $this->user()->id)
            ->where('group_name', $this->input('group_name'))
            ->where('member_name', $this->input('member_name'))
            ->where('album', $this->input('album'))
            ->where('version', $this->input('version'))
            // When updating, the card shouldn't count as a duplicate of itself.
            ->when($this->route('photocard'), fn ($query, $id) => $query->whereKeyNot($id))
            ->exists();
    }
}
