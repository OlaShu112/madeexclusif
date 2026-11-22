<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = ['user_id', 'full_name', 'line1', 'line2', 'city', 'postcode', 'country', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];
}
