<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $fillable = [
        'user_id',
        'domain',
        'price',
        'currency',
        'registrar',
        'registered_at',
        'renews_at',
        'status',
        'sold_price',
        'sold_at',
        'notes',
    ];

    protected $casts = [
        'price'         => 'decimal:2',
        'sold_price'    => 'decimal:2',
        'registered_at' => 'date',
        'renews_at'     => 'date',
        'sold_at'       => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'domain', 'domain')
                    ->whereBelongsTo($this->user);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'domain', 'domain')
                    ->whereBelongsTo($this->user);
    }

    public function outboundCount(): int
    {
        return (int) $this->campaigns()
                          ->withCount('recipients')
                          ->get()
                          ->sum('recipients_count');
    }

    public function sentCount(): int
    {
        return (int) $this->campaigns()
                          ->whereHas('recipients', fn ($q) => $q->whereIn('status', ['sent', 'replied']))
                          ->get()
                          ->reduce(function ($carry, $campaign) {
                              return $carry + $campaign->recipients()->whereIn('status', ['sent', 'replied'])->count();
                          }, 0);
    }

    public function repliedCount(): int
    {
        return (int) $this->campaigns()
                          ->whereHas('recipients', fn ($q) => $q->where('status', 'replied'))
                          ->get()
                          ->reduce(function ($carry, $campaign) {
                              return $carry + $campaign->recipients()->where('status', 'replied')->count();
                          }, 0);
    }

    public function profit(): float
    {
        return (float) (($this->sold_price ?? 0) - $this->price);
    }
}