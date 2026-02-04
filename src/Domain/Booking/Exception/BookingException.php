<?php

declare(strict_types=1);

namespace BookFlow\Domain\Booking\Exception;

use DomainException;

/**
 * Base exception for all booking-related domain errors.
 */
abstract class BookingException extends DomainException
{
}
