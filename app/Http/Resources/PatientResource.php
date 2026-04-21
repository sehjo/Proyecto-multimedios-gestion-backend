<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'lastname'    => $this->lastname,
            'nick'        => $this->nick,
            'suffering'   => $this->suffering,
            'register_by' => $this->register_by,
            'user'        => $this->whenLoaded('user'),
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,
        ];
    }
}
