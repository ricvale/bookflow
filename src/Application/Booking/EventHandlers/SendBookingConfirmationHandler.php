<?php

declare(strict_types=1);

namespace BookFlow\Application\Booking\EventHandlers;

use BookFlow\Domain\Booking\Events\BookingCreated;
use BookFlow\Domain\Resource\ResourceId;
use BookFlow\Domain\Resource\ResourceRepositoryInterface;
use BookFlow\Domain\Shared\Interfaces\MailerInterface;
use BookFlow\Domain\User\UserRepository;

/**
 * Event handler that sends a confirmation email when a booking is created.
 */
final class SendBookingConfirmationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private ResourceRepositoryInterface $resourceRepo,
        private UserRepository $userRepo
    ) {
    }

    public function __invoke(BookingCreated $event): void
    {
        // For demonstration, we send to the admin
        // In a real app, we would send to the user who made the booking
        $user = $this->userRepo->findByEmail('admin@example.com');
        if (!$user) {
            return;
        }

        $resource = $this->resourceRepo->findById(ResourceId::fromString($event->resourceId));
        $resourceName = $resource ? $resource->name() : 'Unknown Resource';

        $subject = "Booking Confirmed: {$resourceName}";

        $body = "
            <h2>Booking Confirmation</h2>
            <p>Hi {$user->name()},</p>
            <p>Your booking for <strong>{$resourceName}</strong> has been confirmed.</p>
            <ul>
                <li><strong>Start:</strong> {$event->startsAt->format('Y-m-d H:i')}</li>
                <li><strong>End:</strong> {$event->endsAt->format('Y-m-d H:i')}</li>
            </ul>
            <br>
            <p>Thank you for using BookFlow!</p>
        ";

        try {
            $this->mailer->send($user->email(), $subject, $body);
        } catch (\Exception $e) {
            error_log('Failed to send booking confirmation email: ' . $e->getMessage());
        }
    }
}
