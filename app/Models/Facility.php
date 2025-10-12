<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    protected $fillable = [
        'name',
        'category',
        'department',
        'description',
        'hours',
        'marker_id',
        'status',
    ];

    public function marker()
    {
        return $this->belongsTo(Marker::class);
    }
}
