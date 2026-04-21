<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $table = 'patient';

    protected $perPage = 20;

    protected $fillable = ['name', 'lastname', 'nick', 'suffering', 'register_by'];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'register_by', 'id');
    }
}
