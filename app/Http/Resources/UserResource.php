<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'roles' => $this->getRoleNames(),
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
            'driver_status' => new DriverStatusResource($this->whenLoaded('driverStatus')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
