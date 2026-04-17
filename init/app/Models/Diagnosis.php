<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Diagnosis
 *
 * @property $id
 * @property $name
 * @property $disease_id
 * @property $patient_id
 * @property $diagnoses_by
 * @property $created_at
 * @property $updated_at
 *
 * @property User $user
 * @property Patient $patient
 * @property Disease $disease
 * @property DiagnosesHasTreatment[] $diagnosesHasTreatments
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Diagnosis extends Model
{
    
    protected $table = 'diagnoses';

    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'disease_id', 'patient_id', 'diagnoses_by'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'diagnoses_by', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function patient()
    {
        return $this->belongsTo(\App\Models\Patient::class, 'patient_id', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function disease()
    {
        return $this->belongsTo(\App\Models\Disease::class, 'disease_id', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function diagnosesHasTreatments()
    {
        return $this->hasMany(\App\Models\DiagnosesHasTreatment::class, 'diagnoses_id', 'id');
    }
    
}
