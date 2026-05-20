<?php

namespace App\Support;

use RuntimeException;

/**
 * Thrown by FoodWasteScanner when a scan cannot be completed. The message is
 * safe to surface to the client; callers map it to a 422 JSON response.
 */
class FoodWasteScanException extends RuntimeException
{
}
