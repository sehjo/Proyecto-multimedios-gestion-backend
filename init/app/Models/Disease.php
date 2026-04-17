<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Disease
 *
 * @property $id
 * @property $name
 * @property $technincal_name
 * @property $description
 * @property $priority_id
 * @property $created_at
 * @property $updated_at
 *
 * @property Priority $priority
 * @property Diagnosis[] $diagnoses
 * @property DiseaseHasTreatment[] $diseaseHasTreatments
 * @property Patient[] $patients
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Disease extends Model
{
    
    protected $table = 'disease';

    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'technincal_name', 'description', 'priority_id'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function priority()
    {
        return $this->belongsTo(\App\Models\Priority::class, 'priority_id', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function diagnoses()
    {
        return $this->hasMany(\App\Models\Diagnosis::class, 'disease_id', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function diseaseHasTreatments()
    {
        return $this->hasMany(\App\Models\DiseaseHasTreatment::class, 'disease_id', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function patients()
    {
        return $this->hasMany(\App\Models\Patient::class, 'suffering', 'id');
    }
    
}
