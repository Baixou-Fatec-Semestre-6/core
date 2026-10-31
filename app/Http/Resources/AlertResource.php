<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertResource extends JsonResource
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
            // 'user_id' => $this->productMonitor->user_id,
            'product_id' => $this->productMonitor->product_id,
            'product_monitor_id' => $this->product_monitor_id,
            'target_price' => $this->target_price,
            'notification_channel' => $this->notification_channel,
            'created_at' => $this->created_at,
        ];
    }
}
