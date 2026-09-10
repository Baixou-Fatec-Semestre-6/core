<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
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
            'store' => $this->store,
            'url' => $this->url,
            'current_price' => $this->current_price,
            'currency' => $this->currency,
            'last_scrape_at' => $this->last_scrape_at,
            'history' => PriceHistoryResource::collection($this->whenLoaded('priceHistories')),
            'min_price' => round($this->priceHistories?->min('price'), 2),
            'avg_price' => round($this->priceHistories?->avg('price'), 2),
            'max_price' => round($this->priceHistories?->max('price'), 2),
        ];
    }
}
