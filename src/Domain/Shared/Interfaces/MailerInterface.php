<?php

declare(strict_types=1);

namespace BookFlow\Domain\Shared\Interfaces;

/**
 * Domain-agnostic interface for sending emails.
 */
interface MailerInterface
{
    /**
     * Send an email.
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param array $options (e.g., from, cc, etc.)
     */
    public function send(string $to, string $subject, string $body, array $options = []): void;
}
