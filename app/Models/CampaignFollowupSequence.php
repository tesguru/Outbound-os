<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignFollowupSequence extends Model
{
    protected $fillable = [
        'campaign_id',
        'template_id',
        'type',
        'sequence',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}