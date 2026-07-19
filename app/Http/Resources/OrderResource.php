<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'invoice_number' => $this->invoice_number,
            'branch' => [
                'id' => $this->branch->id,
                'name' => $this->branch->name,
                'code' => $this->branch->code,
            ],
            'transaction_date' => $this->transaction_date->toDateString(),
            'amount' => (float) $this->amount,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
