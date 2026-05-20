<?php

namespace App\Support;

use RuntimeException;

/**
 * Thrown when a demo device or IP has exhausted its free allowance. The message
 * is client-safe; callers return it as a 429 JSON response.
 */
class DemoQuotaException extends RuntimeException
{
}
