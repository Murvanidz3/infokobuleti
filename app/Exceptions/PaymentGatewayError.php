<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The bank's API could not be reached or refused the request.
 */
class PaymentGatewayError extends RuntimeException {}
