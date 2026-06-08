<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'domain',
        'gmail_label_id',
        'gmail_label_name',
        'template_type',
        'initial_template_id',
        'personal_initial_template_id',
        'company_initial_template_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function initialTemplate()
    {
        return $this->belongsTo(Template::class, 'initial_template_id');
    }

    public function personalInitialTemplate()
    {
        return $this->belongsTo(Template::class, 'personal_initial_template_id');
    }

    public function companyInitialTemplate()
    {
        return $this->belongsTo(Template::class, 'company_initial_template_id');
    }

    public function followupSequences()
    {
        return $this->hasMany(CampaignFollowupSequence::class)->orderBy('sequence');
    }

    public function personalFollowupSequences()
    {
        return $this->hasMany(CampaignFollowupSequence::class)
                    ->where('type', 'personal')
                    ->orderBy('sequence');
    }

    public function companyFollowupSequences()
    {
        return $this->hasMany(CampaignFollowupSequence::class)
                    ->where('type', 'company')
                    ->orderBy('sequence');
    }

    public function gmailAccounts()
    {
        return $this->belongsToMany(GmailAccount::class, 'campaign_gmail_accounts')
                    ->withPivot('recipient_limit')
                    ->withTimestamps();
    }

    public function recipients()
    {
        return $this->hasMany(Recipient::class);
    }

    public function followups()
    {
        return $this->hasMany(Followup::class);
    }

    // ← ADD THIS
    public function websites()
    {
        return $this->hasMany(CampaignWebsite::class);
    }
}