<?php

declare(strict_types=1);

namespace RoundlyConsulting\Plausible\Exceptions;

use Exception;

/**
 * Base class for every exception this package throws, so consumers can
 * `catch (PlausibleException $e)` to handle anything from the client at once.
 */
abstract class PlausibleException extends Exception {}
