<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SocialIntegration extends Model
{
    protected $table = 'social_integrations';

    protected $fillable = [
        'platform',
        'account_name',
        'page_id_or_phone_id',
        'access_token',
        'webhook_verify_token',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'access_token',
    ];

    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable $e) {
            return $value;
        }
    }

    public function statusLabel(): string
    {
        return $this->is_active ? 'Connected' : 'Not Connected';
    }

    public function connectionName(): string
    {
        return $this->account_name ?: ($this->platform === 'facebook' ? 'Facebook Page' : 'WhatsApp Number');
    }
}
