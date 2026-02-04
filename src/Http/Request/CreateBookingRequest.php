<?php

declare(strict_types=1);

namespace BookFlow\Http\Request;

use BookFlow\Http\Exception\ValidationException;
use DateTimeImmutable;
use Throwable;

/**
 * Request DTO for creating a booking.
 *
 * Validates and transforms raw request data into a typed object.
 */
final readonly class CreateBookingRequest
{
    public function __construct(
        public string $resourceId,
        public DateTimeImmutable $startsAt,
        public DateTimeImmutable $endsAt
    ) {
    }

    /**
     * Create from raw request data with validation.
     *
     * @throws ValidationException
     */
    public static function fromArray(array $data): self
    {
        $errors = [];

        // Validate resource_id
        if (!isset($data['resource_id']) || empty($data['resource_id'])) {
            $errors['resource_id'] = 'Resource ID is required';
        }

        // Validate starts_at
        if (!isset($data['starts_at']) || empty($data['starts_at'])) {
            $errors['starts_at'] = 'Start time is required';
        }

        // Validate ends_at
        if (!isset($data['ends_at']) || empty($data['ends_at'])) {
            $errors['ends_at'] = 'End time is required';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors);
        }

        // Parse dates
        try {
            $startsAt = new DateTimeImmutable($data['starts_at']);
        } catch (Throwable) {
            throw ValidationException::invalidField('starts_at', 'Invalid date format');
        }

        try {
            $endsAt = new DateTimeImmutable($data['ends_at']);
        } catch (Throwable) {
            throw ValidationException::invalidField('ends_at', 'Invalid date format');
        }

        // Business validation
        if ($startsAt >= $endsAt) {
            throw ValidationException::invalidField('ends_at', 'End time must be after start time');
        }

        if ($startsAt < new DateTimeImmutable()) {
            throw ValidationException::invalidField('starts_at', 'Start time cannot be in the past');
        }

        return new self(
            resourceId: $data['resource_id'],
            startsAt: $startsAt,
            endsAt: $endsAt
        );
    }
}
