<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Priority
 *
 * @property $id
 * @property $name
 * @property $created_at
 * @property $updated_at
 *
 * @property Disease[] $diseases
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Priority extends Model
{
    
    protected $table = 'priority';

    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function diseases()
    {
        return $this->hasMany(\App\Models\Disease::class, 'priority_id', 'id');
    }
    
}
