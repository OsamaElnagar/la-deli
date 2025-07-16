<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusHistoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'changed_by' => new UserResource($this->whenLoaded('changedBy')),
            'changed_at' => $this->changed_at?->toISOString(),
        ];
    }
}
