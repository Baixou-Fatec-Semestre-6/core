<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductMonitorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'last_error_reason' => $this->last_error_reason,
            'target_price' => $this->target_price,
            'currency' => $this->currency,
            'notification_channel' => $this->notification_channel,
            'created_at' => $this->created_at,
        ];
    }
}
