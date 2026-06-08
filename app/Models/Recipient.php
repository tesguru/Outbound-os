<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'gmail_account_id',
        'email',
        'first_name',
        'company_name',
        'personalization_type',
        'thread_id',
        'message_id',
        'status',
        'is_bounced',
    ];

    protected $casts = [
        'is_bounced' => 'boolean',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function gmailAccount()
    {
        return $this->belongsTo(GmailAccount::class);
    }

    public function followups()
    {
        return $this->hasMany(Followup::class);
    }
}