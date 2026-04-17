<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DiagnosesHasTreatment
 *
 * @property $id
 * @property $diagnoses_id
 * @property $drugs
 * @property $descriptions
 * @property $created_at
 * @property $updated_at
 *
 * @property Drug $drug
 * @property Diagnosis $diagnosis
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class DiagnosesHasTreatment extends Model
{
    
    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['diagnoses_id', 'drugs', 'descriptions'];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function drug()
    {
        return $this->belongsTo(\App\Models\Drug::class, 'drugs', 'id');
    }
    
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function diagnosis()
    {
        return $this->belongsTo(\App\Models\Diagnosis::class, 'diagnoses_id', 'id');
    }
    
}
