<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'category',
        'subject',
        'body',
        'has_price',
    ];

    protected $casts = [
        'has_price' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'initial_template_id');
    }
}