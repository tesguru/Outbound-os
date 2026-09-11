<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'user_id',
        'source_campaign_id',
        'email',
        'first_name',
        'company_name',
        'domain',
        'status',
        'website',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sourceCampaign()
    {
        return $this->belongsTo(Campaign::class, 'source_campaign_id');
    }

    public function domainModel()
    {
        return $this->belongsTo(Domain::class, 'domain', 'domain')
                    ->where('user_id', $this->user_id);
    }
}