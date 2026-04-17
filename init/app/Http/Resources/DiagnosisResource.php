<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiagnosisResource extends JsonResource
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
            'name'         => $this->name,
            'disease_id'   => $this->disease_id,
            'disease'      => $this->whenLoaded('disease'),
            'patient_id'   => $this->patient_id,
            'patient'      => $this->whenLoaded('patient'),
            'diagnoses_by' => $this->diagnoses_by,
            'user'         => $this->whenLoaded('user'),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
