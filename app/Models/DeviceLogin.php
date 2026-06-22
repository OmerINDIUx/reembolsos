<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceLogin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_hash',
        'session_id',
        'ip_address',
        'approx_location',
        'user_agent',
        'device_label',
        'is_new_device',
        'risk_score',
        'risk_reasons',
        'simultaneous_devices_count',
        'shared_accounts_count',
        'logged_in_at',
        'last_seen_at',
        'logged_out_at',
    ];

    protected function casts(): array
    {
        return [
            'logged_in_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'logged_out_at' => 'datetime',
            'is_new_device' => 'boolean',
            'risk_reasons' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDeviceCodeAttribute(): string
    {
        return strtoupper(substr($this->device_hash, 0, 8));
    }
}
