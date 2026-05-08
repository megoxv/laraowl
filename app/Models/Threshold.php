<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Threshold extends Model
{
    protected $fillable = [
        'project_id',
        'type',
        'key',
        'value',
        'is_enabled',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
