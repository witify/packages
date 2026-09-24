<?php

namespace Witify\Notifications\QueryBuilders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Witify\Notifications\Models\Notification;
use Witify\Support\QueryBuilder\BaseQueryBuilder;

/**
 * @template TModel of Notification
 *
 * @extends BaseQueryBuilder<TModel>
 */
class NotificationQueryBuilder extends BaseQueryBuilder
{
    /**
     * @return QueryBuilder<TModel>
     */
    public function index(Request $request): QueryBuilder
    {
        $query = $this
            ->protect()
            ->search(['notifications.text']);

        return QueryBuilder::for($query)
            ->allowedIncludes([])
            ->allowedSorts('created_at', 'updated_at')
            ->allowedFilters([
                AllowedFilter::callback('read', function (self $query, mixed $value): void {
                    $read = $this->toBoolean($value);

                    if ($read === null) {
                        return;
                    }

                    $read
                        ? $query->whereNotNull('read_at')
                        : $query->whereNull('read_at');
                }),
            ])
            ->defaultSort('-created_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    /**
     * @return $this
     */
    public function protect(): static
    {
        parent::protect();

        /** @var Model $user */
        $user = Auth::user();

        $this
            ->where('notifiable_id', $user->getKey())
            ->where('notifiable_type', $user->getMorphClass());

        return $this;
    }
}
