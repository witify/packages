<?php

namespace Witify\Notifications\Tests\Fixtures;

use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Witify\Notifications\Herald\HeraldNotifiable;
use Witify\Notifications\Herald\HeraldUser;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string $locale
 * @property string|null $timezone
 * @property string|null $phone
 * @property array<int, array{key: string, channels: array<string, bool>}>|null $notification_settings
 * @property-read string $full_name
 */
class User extends Authenticatable implements HasLocalePreference, HeraldUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    protected $guarded = [];

    protected $casts = [
        'notification_settings' => 'array',
    ];

    protected static function newFactory(): UserFactory
    {
        return new UserFactory;
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function preferredLocale(): string
    {
        return $this->locale;
    }

    public function heraldNotifiable(): HeraldNotifiable
    {
        return new HeraldNotifiable(
            id: 'user' . $this->id,
            full_name: $this->full_name,
            first_name: $this->first_name,
            last_name: $this->last_name,
            email: $this->email,
            phone: $this->phone,
            locale: $this->locale,
            timezone: $this->timezone,
            user: $this,
        );
    }

    public function getNotificationSettings(): array
    {
        return $this->notification_settings ?? [];
    }

    public function setNotificationSettings(array $settings): void
    {
        $this->notification_settings = $settings;
        $this->save();
    }
}
