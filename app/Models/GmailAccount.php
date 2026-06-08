<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GmailAccount extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'name',
        'avatar',
        'google_token',
        'google_refresh_token',
        'daily_limit',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'google_token' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_gmail_accounts')
                    ->withPivot('recipient_limit')
                    ->withTimestamps();
    }

    public function recipients()
    {
        return $this->hasMany(Recipient::class);
    }
}