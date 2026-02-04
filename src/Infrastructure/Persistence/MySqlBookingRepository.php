<?php

declare(strict_types=1);

namespace BookFlow\Infrastructure\Persistence;

use BookFlow\Application\Shared\Interfaces\TenantContextInterface;
use BookFlow\Domain\Booking\Booking;
use BookFlow\Domain\Booking\BookingId;
use BookFlow\Domain\Booking\BookingRepositoryInterface;
use BookFlow\Domain\Booking\BookingStatus;
use BookFlow\Domain\Resource\ResourceId;
use BookFlow\Domain\Shared\DateRange;
use BookFlow\Domain\Shared\TenantId;
use DateTimeImmutable;
use PDO;

/**
 * MySQL implementation of the BookingRepository.
 */
final class MySqlBookingRepository implements BookingRepositoryInterface
{
    public function __construct(
        private PDO $pdo,
        private TenantContextInterface $tenantContext
    ) {
    }

    public function save(Booking $booking): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO bookings (id, tenant_id, resource_id, starts_at, ends_at, status, google_event_id, created_at)
            VALUES (:id, :tenant_id, :resource_id, :starts_at, :ends_at, :status, :google_event_id, :created_at)
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                google_event_id = VALUES(google_event_id),
                starts_at = VALUES(starts_at),
                ends_at = VALUES(ends_at)
        ');

        $stmt->execute([
            'id' => $booking->id()->toString(),
            'tenant_id' => $booking->tenantId()->toString(),
            'resource_id' => $booking->resourceId()->toString(),
            'starts_at' => $booking->timeSlot()->startsAt()->format('Y-m-d H:i:s'),
            'ends_at' => $booking->timeSlot()->endsAt()->format('Y-m-d H:i:s'),
            'status' => $booking->status()->value,
            'google_event_id' => $booking->googleEventId(),
            'created_at' => $booking->createdAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function findById(string $id): ?Booking
    {
        $tenantId = $this->tenantContext->getTenantId();

        $stmt = $this->pdo->prepare('
            SELECT * FROM bookings 
            WHERE id = :id AND tenant_id = :tenant_id
        ');

        $stmt->execute([
            'id' => $id,
            'tenant_id' => $tenantId->toString(),
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function findByBookingId(BookingId $id): ?Booking
    {
        return $this->findById($id->toString());
    }

    public function findConflicting(string $resourceId, DateTimeImmutable $startsAt, DateTimeImmutable $endsAt): array
    {
        $tenantId = $this->tenantContext->getTenantId();

        $stmt = $this->pdo->prepare('
            SELECT * FROM bookings 
            WHERE tenant_id = :tenant_id
              AND resource_id = :resource_id
              AND status = :status
              AND starts_at < :ends_at
              AND ends_at > :starts_at
        ');

        $stmt->execute([
            'tenant_id' => $tenantId->toString(),
            'resource_id' => $resourceId,
            'status' => BookingStatus::CONFIRMED->value,
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
        ]);

        return array_map(
            fn ($row) => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function findAll(): array
    {
        $tenantId = $this->tenantContext->getTenantId();

        $stmt = $this->pdo->prepare('
            SELECT * FROM bookings 
            WHERE tenant_id = :tenant_id
            ORDER BY starts_at DESC
        ');

        $stmt->execute(['tenant_id' => $tenantId->toString()]);

        return array_map(
            fn ($row) => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * Hydrate a Booking from a database row.
     *
     * Uses the reconstitute pattern to avoid triggering domain events.
     */
    private function hydrate(array $row): Booking
    {
        return Booking::reconstitute(
            id: BookingId::fromString($row['id']),
            tenantId: TenantId::fromString($row['tenant_id']),
            resourceId: ResourceId::fromString($row['resource_id']),
            timeSlot: new DateRange(
                new DateTimeImmutable($row['starts_at']),
                new DateTimeImmutable($row['ends_at'])
            ),
            status: BookingStatus::from($row['status']),
            createdAt: new DateTimeImmutable($row['created_at']),
            googleEventId: $row['google_event_id']
        );
    }
}
