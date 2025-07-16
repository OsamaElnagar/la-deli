<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverStatusResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'status' => $this->status,
            'current_location' => $this->current_location,
            'current_order' => new OrderResource($this->whenLoaded('currentOrder')),
            'last_location_update' => $this->last_location_update?->toISOString(),
        ];
    }
}
