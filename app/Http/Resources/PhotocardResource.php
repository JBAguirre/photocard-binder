<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotocardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'group_name' => $this->group_name,
            'member_name' => $this->member_name,
            'album' => $this->album,
            'version' => $this->version,
            'card_type' => $this->card_type->value,
            'status' => $this->status->value,
            'condition' => $this->condition?->value,
            'notes' => $this->notes,
            'image_path' => $this->image_path,
            'created_at' => $this->created_at,
        ];
    }
}
