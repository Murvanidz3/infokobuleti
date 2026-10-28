<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The plan cannot be bought for this listing (inactive plan, wrong module, listing not live).
 */
class PurchaseNotAllowed extends RuntimeException {}
