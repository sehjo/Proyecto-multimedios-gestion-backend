<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DiseaseHasTreatment
 *
 * @property $id
 * @property $descriptions
 * @property $disease_id
 * @property $drugs
 * @property $created_at
 * @property $updated_at
 *
 * @property Drug $drug
 * @property Disease $disease
 * @package App
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class DiseaseHasTreatment extends Model
{
    
    protected $perPage = 20;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['descriptions', 'disease_id', 'drugs'];


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
    public function disease()
    {
        return $this->belongsTo(\App\Models\Disease::class, 'disease_id', 'id');
    }
    
}
