<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nic_passport' => $this->nic_passport,
            'mobile_number' => $this->mobile_number,
            'name' => $this->name,
            'status' => $this->status,
            'registered_by' => $this->registeredBy?->name,
            'activated_at' => $this->activated_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            // Only load points if loaded/referenced, otherwise calculate
            'points_balance' => $this->points_balance,
        ];
    }
}
