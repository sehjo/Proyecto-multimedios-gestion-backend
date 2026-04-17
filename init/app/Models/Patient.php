<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Patient
 *
 * @property $id
 * @property $name
 * @property $lastname
 * @property $nick
 * @property $suffering
 * @property $register_by
 * @property $created_at
 * @property $updated_at
 *
 * @property User $user
 * 
 * 
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Patient extends Model
{
    
    protected $table = 'patient';

    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'lastname', 'nick', 'suffering', 'register_by'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'register_by', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function disease()
    {
        return $this->belongsTo(\App\Models\Disease::class, 'suffering', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function diagnoses()
    {
        return $this->hasMany(\App\Models\Diagnosis::class, 'patient_id', 'id');
    }
    
}
