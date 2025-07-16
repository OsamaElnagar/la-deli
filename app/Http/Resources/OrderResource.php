<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_code' => $this->order_code,
            'invoice_number' => $this->invoice_number,
            'delivery_type' => $this->delivery_type,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'notes' => $this->notes,

            // Customer info (for home deliveries)
            'customer_name' => $this->customer_name,
            'customer_address' => $this->customer_address,
            'customer_phone' => $this->customer_phone,
            'customer_coordinates' => $this->customer_coordinates,

            // Relationships
            'source_branch' => new BranchResource($this->whenLoaded('sourceBranch')),
            'destination_branch' => new BranchResource($this->whenLoaded('destinationBranch')),
            'pharmacist' => new UserResource($this->whenLoaded('pharmacist')),
            'driver' => new UserResource($this->whenLoaded('driver')),
            'created_by' => new UserResource($this->whenLoaded('createdBy')),

            // Order items
            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            // Status history
            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistories')),

            // Media
            'invoice_url' => $this->getFirstMediaUrl('invoices'),
            'delivery_proof_urls' => $this->getMedia('delivery_proof')->map(fn($media) => $media->getUrl()),

            // Timestamps
            'prepared_at' => $this->prepared_at?->toISOString(),
            'picked_up_at' => $this->picked_up_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
