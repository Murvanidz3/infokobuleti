<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A callback whose signature or sender could not be verified. Nothing is changed.
 */
class InvalidPaymentCallback extends RuntimeException {}
