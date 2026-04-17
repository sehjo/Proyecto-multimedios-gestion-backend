<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiseaseHasTreatmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'descriptions' => $this->descriptions,
            'disease_id'   => $this->disease_id,
            'disease'      => $this->whenLoaded('disease'),
            'drugs'        => $this->drugs,
            'drug'         => $this->whenLoaded('drug'),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
