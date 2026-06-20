<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\LogUser
 */
class LogUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'action'       => $this->action->value,
            'performed_by' => $this->whenLoaded('performedBy', fn () => $this->performedBy
                ? ['id' => $this->performedBy->id, 'name' => $this->performedBy->name]
                : null),
            'target_user'  => $this->whenLoaded('targetUser', fn () => $this->targetUser
                ? ['id' => $this->targetUser->id, 'name' => $this->targetUser->name]
                : null),
            'changes'      => $this->changes,
            'timestamp'    => $this->timestamp,
        ];
    }
}
