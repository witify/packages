<?php

namespace Witify\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Witify\Support\Model\SanitizesHtmlTrait;

/**
 * @property int $id
 * @property string $notification_class
 * @property string $locale
 * @property string $channel
 * @property string $subject
 * @property string $message
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationMessage whereChannel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationMessage whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationMessage whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationMessage whereNotificationClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationMessage whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationMessage whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class NotificationMessage extends Model
{
    use SanitizesHtmlTrait;

    const CHANNEL_MAIL = 'mail';

    const CHANNEL_DATABASE = 'database';

    protected $fillable = [
        'notification_class',
        'locale',
        'channel',
        'subject',
        'message',
    ];

    /**
     * The message is edited with BaseRichText and stores HTML.
     *
     * @return array<int, string>
     */
    public function sanitizeHtmlFields(): array
    {
        return ['message'];
    }
}
