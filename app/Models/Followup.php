<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Followup extends Model
{
    protected $fillable = [
        'campaign_id',
        'recipient_id',
        'template_id',
        'draft_id',
        'thread_id',
        'price',
        'sequence',
        'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function recipient()
    {
        return $this->belongsTo(Recipient::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}