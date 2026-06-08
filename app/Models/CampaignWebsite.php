<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignWebsite extends Model
{
    protected $fillable = ['campaign_id', 'url'];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}