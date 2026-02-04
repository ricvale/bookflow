<?php

declare(strict_types=1);

namespace BookFlow\Http\Request;

use BookFlow\Http\Exception\ValidationException;

/**
 * Request DTO for creating a resource.
 */
final readonly class CreateResourceRequest
{
    public function __construct(
        public string $name,
        public string $description = ''
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

        if (!isset($data['name']) || empty(trim($data['name']))) {
            $errors['name'] = 'Resource name is required';
        } elseif (strlen($data['name']) > 255) {
            $errors['name'] = 'Resource name must be less than 255 characters';
        }

        if (isset($data['description']) && strlen($data['description']) > 1000) {
            $errors['description'] = 'Description must be less than 1000 characters';
        }

        if (!empty($errors)) {
            throw ValidationException::withErrors($errors);
        }

        return new self(
            name: trim($data['name']),
            description: trim($data['description'] ?? '')
        );
    }
}
