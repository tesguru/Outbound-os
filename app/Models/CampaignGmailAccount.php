<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignGmailAccount extends Model
{
    protected $fillable = [
        'campaign_id',
        'gmail_account_id',
        'recipient_limit',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function gmailAccount()
    {
        return $this->belongsTo(GmailAccount::class);
    }
}