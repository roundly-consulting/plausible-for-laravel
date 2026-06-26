<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use RoundlyConsulting\Plausible\Concerns\Trackable;

/**
 * Test fixture exercising the {@see Trackable} trait.
 *
 * @property int $total
 */
final class TrackedOrder extends Model
{
    use Trackable;

    protected $guarded = [];

    protected $table = 'tracked_orders';

    public $timestamps = false;

    /** @var array<string, string> */
    protected array $plausibleEvents = [
        'created' => 'Order Placed',
        'updated' => 'Order Updated',
        'deleted' => 'Order Cancelled',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function plausibleEventProps(string $event): array
    {
        return ['total' => $this->getAttribute('total')];
    }
}
