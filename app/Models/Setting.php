<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasFactory;

    /** Default values used when a key is missing. */
    public const DEFAULTS = [
        'site_title' => 'ITsian CRM',
        'admin_logo' => null,
    ];

    protected $fillable = [
        'key',
        'value',
    ];

    /** Per-request cache of the settings table. */
    protected static ?array $cache = null;

    public static function get($key, $default = null)
    {
        $all = static::allSettings();

        return array_key_exists($key, $all) && $all[$key] !== null && $all[$key] !== ''
            ? $all[$key]
            : $default;
    }

    public static function set($key, $value)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        static::$cache = null;

        return $setting;
    }

    /** Public URL of the uploaded admin logo, or null. */
    public static function logoUrl(): ?string
    {
        $path = static::get('admin_logo');

        return $path && Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    protected static function allSettings(): array
    {
        if (static::$cache === null) {
            try {
                static::$cache = Schema::hasTable('settings')
                    ? self::query()->pluck('value', 'key')->all()
                    : [];
            } catch (\Throwable $e) {
                static::$cache = [];
            }
        }

        return static::$cache;
    }
}
