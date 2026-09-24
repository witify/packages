<?php

namespace Witify\Notifications\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Witify\Notifications\QueryBuilders\NotificationQueryBuilder;

/**
 * @property int $id
 * @property string $type
 * @property string $notifiable_type
 * @property int $notifiable_id
 * @property string|null $model_type
 * @property int|null $model_id
 * @property string $text
 * @property string|null $path
 * @property string|null $url
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model $notifiable
 *
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection<int, static> all($columns = ['*'])
 * @method static NotificationQueryBuilder<static>|Notification dateBetween(string $column, array<int, string>|null $range)
 * @method static NotificationQueryBuilder<static>|Notification exclude(...$columns)
 * @method static \Illuminate\Notifications\DatabaseNotificationCollection<int, static> get($columns = ['*'])
 * @method static NotificationQueryBuilder<static>|Notification getQueryAsString()
 * @method static NotificationQueryBuilder<static>|Notification index(\Illuminate\Http\Request $request)
 * @method static NotificationQueryBuilder<static>|Notification newModelQuery()
 * @method static NotificationQueryBuilder<static>|Notification newQuery()
 * @method static NotificationQueryBuilder<static>|Notification protect()
 * @method static NotificationQueryBuilder<static>|Notification query()
 * @method static \Witify\Notifications\QueryBuilders\NotificationQueryBuilder<static>|Notification read()
 * @method static NotificationQueryBuilder<static>|Notification search(array<int, string|callable|\Witify\Support\Search\SearchConstraintInterface>|null $items = [])
 * @method static \Witify\Notifications\QueryBuilders\NotificationQueryBuilder<static>|Notification unread()
 * @method static NotificationQueryBuilder<static>|Notification whereCreatedAt($value)
 * @method static NotificationQueryBuilder<static>|Notification whereId($value)
 * @method static NotificationQueryBuilder<static>|Notification whereModelId($value)
 * @method static NotificationQueryBuilder<static>|Notification whereModelType($value)
 * @method static NotificationQueryBuilder<static>|Notification whereNotifiableId($value)
 * @method static NotificationQueryBuilder<static>|Notification whereNotifiableType($value)
 * @method static NotificationQueryBuilder<static>|Notification wherePath($value)
 * @method static NotificationQueryBuilder<static>|Notification whereReadAt($value)
 * @method static NotificationQueryBuilder<static>|Notification whereText($value)
 * @method static NotificationQueryBuilder<static>|Notification whereType($value)
 * @method static NotificationQueryBuilder<static>|Notification whereUpdatedAt($value)
 * @method static NotificationQueryBuilder<static>|Notification whereUrl($value)
 *
 * @mixin \Eloquent
 */
class Notification extends DatabaseNotification
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    protected $fillable = ['url', 'path', 'model_id', 'model_type'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder  $query
     * @return NotificationQueryBuilder<self>
     */
    public function newEloquentBuilder($query): NotificationQueryBuilder
    {
        return new NotificationQueryBuilder($query);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Mutators
    |--------------------------------------------------------------------------
    */

    //

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    //
}
