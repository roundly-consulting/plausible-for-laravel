<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use RoundlyConsulting\Plausible\Concerns\Trackable;

/**
 * Fixture exercising the {@see Trackable} trait with all defaults (no overrides).
 */
final class SimpleTrackedModel extends Model
{
    use Trackable;

    protected $guarded = [];

    protected $table = 'simple_tracked_models';

    public $timestamps = false;

    /** @var array<string, string> */
    protected array $plausibleEvents = [
        'created' => 'Thing Made',
    ];
}
