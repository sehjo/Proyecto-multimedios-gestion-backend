<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Drug
 *
 * @property $id
 * @property $name
 * @property $description
 * @property $type
 * @property $created_at
 * @property $updated_at
 *
 * @property DiagnosesHasTreatment[] $diagnosesHasTreatments
 * @property DiseaseHasTreatment[] $diseaseHasTreatments
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Drug extends Model
{
    
    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'description', 'type'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function diagnosesHasTreatments()
    {
        return $this->hasMany(\App\Models\DiagnosesHasTreatment::class, 'drugs', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function diseaseHasTreatments()
    {
        return $this->hasMany(\App\Models\DiseaseHasTreatment::class, 'drugs', 'id');
    }
    
}
