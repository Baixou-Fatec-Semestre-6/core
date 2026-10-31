<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceNotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->product;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'product_title' => $product?->name,
            'product_image_url' => $product?->image_url,
            'old_price' => $this->old_price,
            'new_price' => $this->new_price,
            'is_read' => $this->is_read,
            'created_at' => $this->created_at,
        ];
    }
}
