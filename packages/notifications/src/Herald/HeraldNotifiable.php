<?php

namespace Witify\Notifications\Herald;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

/**
 * @implements Arrayable<string, mixed>
 */
class HeraldNotifiable implements Arrayable, HasLocalePreference
{
    use Notifiable;

    public readonly string $id;

    public readonly string $full_name;

    public readonly string $first_name;

    public readonly string $last_name;

    public readonly ?string $email;

    public readonly ?string $locale;

    public readonly ?string $phone;

    public readonly string $timezone;

    /**
     * The user model behind this recipient, when there is one.
     *
     * @var (Model&IsHeraldNotifiable)|null
     */
    public readonly ?Model $user;

    /**
     * @param  (Model&IsHeraldNotifiable)|null  $user
     */
    public function __construct(
        string $full_name,
        string $first_name,
        string $last_name,
        ?string $email,
        string $locale = 'en',
        ?string $id = null,
        ?string $phone = null,
        ?string $timezone = null,
        ?Model $user = null
    ) {
        if ($id == null) {
            $this->id = md5($email . $phone . $full_name);
        } else {
            $this->id = $id;
        }
        $this->full_name = $full_name;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->email = $email;
        $this->locale = $locale;
        $this->phone = $phone;
        $this->timezone = $timezone ?? Herald::timezone();
        $this->user = $user;
    }

    public function preferredLocale(): string
    {
        return $this->locale;
    }

    /**
     * What identifies this recipient. Real sending routes on the address, but
     * Laravel's notification fake keys what it records by getKey(): without it a
     * faked send throws and a test of a Herald notification records nothing.
     */
    public function getKey(): string
    {
        return $this->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'locale' => $this->locale,
            'phone' => $this->phone,
            'timezone' => $this->timezone,
        ];
    }
}
