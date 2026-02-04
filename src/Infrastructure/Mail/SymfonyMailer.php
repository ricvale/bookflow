<?php

declare(strict_types=1);

namespace BookFlow\Infrastructure\Mail;

use BookFlow\Domain\Shared\Interfaces\MailerInterface;
use Symfony\Component\Mailer\MailerInterface as SymfonyMailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Symfony Mailer implementation of MailerInterface.
 */
final class SymfonyMailer implements MailerInterface
{
    public function __construct(
        private SymfonyMailerInterface $mailer,
        private string $defaultFrom
    ) {
    }

    public function send(string $to, string $subject, string $body, array $options = []): void
    {
        $email = (new Email())
            ->from($options['from'] ?? $this->defaultFrom)
            ->to($to)
            ->subject($subject)
            ->html($body);

        $this->mailer->send($email);
    }
}
