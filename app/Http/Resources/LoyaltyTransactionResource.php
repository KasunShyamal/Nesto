<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'points' => $this->points,
            'type' => $this->type,
            'description' => $this->description,
            'order' => $this->when($this->order_id !== null, function () {
                return [
                    'id' => $this->order->id,
                    'invoice_number' => $this->order->invoice_number,
                    'branch' => $this->order->branch->name,
                    'amount' => (float) $this->order->amount,
                    'transaction_date' => $this->order->transaction_date->toDateString(),
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
