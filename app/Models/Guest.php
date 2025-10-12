<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guest extends Model
{
    protected $fillable = [
        'name',
        'role'
    ];

    public function notes()
    {
        return $this->hasMany(Note::class);
    }
    public function feedbacks(){
        return $this->hasMany(Feedback::class);
    }
    public function searchLogs(){
        return $this->hasMany(SearchLog::class);
    }
}
