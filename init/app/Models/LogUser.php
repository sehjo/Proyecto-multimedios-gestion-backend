<?php

namespace App\Models;

use App\Enums\UserLogAction;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogUser extends Model
{
    public $timestamps = false;

    protected $table = 'log_users';

    protected $fillable = [
        'performed_by_id',
        'target_user_id',
        'action',
        'changes',
        'timestamp',
    ];

    protected function casts(): array
    {
        return [
            'action'    => UserLogAction::class,
            'timestamp' => 'datetime',
        ];
    }

    /**
     * Store `changes` as JSON_UNESCAPED_UNICODE so accents (á, é, ñ) stay
     * readable in the DB; decode on read.
     */
    protected function changes(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === null ? null : json_decode($value, true),
            set: fn ($value) => $value === null
                ? null
                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
