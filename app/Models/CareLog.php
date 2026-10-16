<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareLog extends Model
{
    protected $fillable = ['plant_id', 'action', 'xp_gain', 'note'];
}
