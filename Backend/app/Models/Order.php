<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Listeners\OrderStatusChangedHandler;
use Closure;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'restaurant_id',
        'status',
        'total',
        'restaurant_name_snapshot',
    ];

    private static array $onStatusChanged = [];

    protected static function booted(): void
    {
        static::onStatusChanged(new OrderStatusChangedHandler());
    }

    protected function casts(): array
    {
        return [
            'total' => 'float',
            'status' => OrderStatus::class,
        ];
    }

    public static function onStatusChanged(Closure|callable $handler): void
    {
        static::$onStatusChanged[] = $handler;
    }


    public static function clearStatusChangedHandlers(): void
    {
        static::$onStatusChanged = [];
    }

    public function transitionTo(OrderStatus $newStatus): void
    {
        $previousStatus = $this->status;

        $this->update(['status' => $newStatus->value]);

        $order = $this;
        DB::afterCommit(function () use ($order, $previousStatus, $newStatus) {
            foreach (static::$onStatusChanged as $handler) {
                $handler($order, $previousStatus, $newStatus);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(OrderDiscount::class);
    }

    public function address(): HasOne
    {
        return $this->hasOne(OrderAddress::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }
}
