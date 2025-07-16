<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'coordinates' => $this->coordinates,
            'is_active' => $this->is_active,
        ];
    }
}
