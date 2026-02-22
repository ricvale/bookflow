<?php

declare(strict_types=1);

namespace BookFlow\Infrastructure;

use BookFlow\Application\Auth\JwtGeneratorInterface;
use BookFlow\Application\Auth\JwtValidatorInterface;
use BookFlow\Application\Auth\LoginUser;
use BookFlow\Application\Booking\CalendarSyncService;
use BookFlow\Application\Booking\CancelBooking;
use BookFlow\Application\Booking\CreateBooking;
use BookFlow\Application\Booking\EventHandlers\RemoveBookingFromCalendarHandler;
use BookFlow\Application\Booking\EventHandlers\SendBookingConfirmationHandler;
use BookFlow\Application\Booking\EventHandlers\SyncBookingToCalendarHandler;
use BookFlow\Application\Resource\CreateResource;
use BookFlow\Application\Shared\Interfaces\EventDispatcherInterface;
use BookFlow\Application\Shared\Interfaces\TenantContextInterface;
use BookFlow\Application\Shared\Interfaces\UserContextInterface;
use BookFlow\Domain\Booking\BookingRepositoryInterface;
use BookFlow\Domain\Booking\CancellationPolicy;
use BookFlow\Domain\Booking\Events\BookingCancelled;
use BookFlow\Domain\Booking\Events\BookingCreated;
use BookFlow\Domain\Booking\Interfaces\CalendarClientInterface;
use BookFlow\Domain\Resource\ResourceRepositoryInterface;
use BookFlow\Domain\Shared\Interfaces\MailerInterface;
use BookFlow\Domain\User\UserRepository;
use BookFlow\Http\Controllers\AuthController;
use BookFlow\Http\Controllers\BookingController;
use BookFlow\Http\Controllers\GoogleAuthController;
use BookFlow\Http\Controllers\ResourceController;
use BookFlow\Http\Middleware\AuthMiddleware;
use BookFlow\Http\Router;
use BookFlow\Infrastructure\Auth\InMemoryTenantContext;
use BookFlow\Infrastructure\Auth\InMemoryUserContext;
use BookFlow\Infrastructure\Auth\JwtTokenGenerator;
use BookFlow\Infrastructure\Calendar\GoogleCalendarClient;
use BookFlow\Infrastructure\Events\InMemoryEventDispatcher;
use BookFlow\Infrastructure\Mail\SymfonyMailer;
use BookFlow\Infrastructure\Persistence\MySqlBookingRepository;
use BookFlow\Infrastructure\Persistence\MySqlResourceRepository;
use BookFlow\Infrastructure\Persistence\MySqlUserRepository;
use PDO;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

/**
 * Application container configuration.
 *
 * Wires all dependencies together.
 */
final class ContainerConfig
{
    /**
     * Configure and return a fully wired container.
     */
    public static function configure(Container $container): Container
    {
        // Environment helper
        $getEnv = fn (string $key, string $default): string =>
            $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;

        // ============================================================
        // Infrastructure - Core Services
        // ============================================================

        $container->singleton(PDO::class, function () use ($getEnv): PDO {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $getEnv('DB_HOST', 'db'),
                $getEnv('DB_PORT', '3306'),
                $getEnv('DB_NAME', 'bookflow'),
            );

            return new PDO(
                $dsn,
                $getEnv('DB_USER', 'bookflow'),
                $getEnv('DB_PASS', 'bookflow'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        });

        // ============================================================
        // Infrastructure - Context Services (Singletons for request lifetime)
        // ============================================================

        $container->singleton(TenantContextInterface::class, fn (): InMemoryTenantContext => new InMemoryTenantContext());
        $container->singleton(UserContextInterface::class, fn (): InMemoryUserContext => new InMemoryUserContext());

        // ============================================================
        // Infrastructure - Auth
        // ============================================================

        $container->singleton(JwtTokenGenerator::class, function () use ($getEnv): JwtTokenGenerator {
            return new JwtTokenGenerator(
                secret: $getEnv('JWT_SECRET', 'fallback_secret_must_be_32_characters_long_minimum_!!')
            );
        });

        // JWT interfaces point to the same implementation
        $container->bind(JwtGeneratorInterface::class, fn (Container $c) => $c->get(JwtTokenGenerator::class));
        $container->bind(JwtValidatorInterface::class, fn (Container $c) => $c->get(JwtTokenGenerator::class));

        // ============================================================
        // Infrastructure - External Services
        // ============================================================

        $container->singleton(GoogleCalendarClient::class, function () use ($getEnv): GoogleCalendarClient {
            return new GoogleCalendarClient(
                clientId: $getEnv('GOOGLE_CLIENT_ID', 'your-client-id'),
                clientSecret: $getEnv('GOOGLE_CLIENT_SECRET', 'your-client-secret'),
                redirectUri: $getEnv('GOOGLE_REDIRECT_URI', 'http://localhost:8000/api/auth/google/callback')
            );
        });

        $container->bind(CalendarClientInterface::class, fn (Container $c) => $c->get(GoogleCalendarClient::class));

        $container->singleton(MailerInterface::class, function () use ($getEnv): MailerInterface {
            $transport = Transport::fromDsn($getEnv('MAILER_DSN', 'smtp://localhost'));
            return new SymfonyMailer(
                new Mailer($transport),
                $getEnv('MAILER_FROM', 'hello@bookflow.local')
            );
        });

        // ============================================================
        // Infrastructure - Repositories
        // ============================================================

        $container->singleton(MySqlBookingRepository::class, fn (Container $c) => new MySqlBookingRepository(
            $c->get(PDO::class),
            $c->get(TenantContextInterface::class)
        ));

        $container->singleton(MySqlResourceRepository::class, fn (Container $c) => new MySqlResourceRepository(
            $c->get(PDO::class),
            $c->get(TenantContextInterface::class)
        ));

        $container->singleton(MySqlUserRepository::class, fn (Container $c) => new MySqlUserRepository(
            $c->get(PDO::class)
        ));

        // Repository interfaces
        $container->bind(BookingRepositoryInterface::class, fn (Container $c) => $c->get(MySqlBookingRepository::class));
        $container->bind(ResourceRepositoryInterface::class, fn (Container $c) => $c->get(MySqlResourceRepository::class));
        $container->bind(UserRepository::class, fn (Container $c) => $c->get(MySqlUserRepository::class));

        // ============================================================
        // Infrastructure - Event Dispatcher
        // ============================================================

        // ============================================================
        // Application - Services
        // ============================================================

        $container->singleton(CalendarSyncService::class, fn (Container $c) => new CalendarSyncService(
            $c->get(BookingRepositoryInterface::class),
            $c->get(ResourceRepositoryInterface::class),
            $c->get(UserRepository::class),
            $c->get(CalendarClientInterface::class),
            $c->get(UserContextInterface::class)
        ));

        // ============================================================
        // Infrastructure - Event Dispatcher
        // ============================================================

        $container->singleton(InMemoryEventDispatcher::class, function (Container $c) {
            $dispatcher = new InMemoryEventDispatcher();

            // Register event handlers for calendar sync
            $calendarSync = $c->get(CalendarSyncService::class);

            $dispatcher->subscribe(
                BookingCreated::class,
                new SyncBookingToCalendarHandler($calendarSync, $c->get(BookingRepositoryInterface::class))
            );

            $dispatcher->subscribe(
                BookingCreated::class,
                new SendBookingConfirmationHandler(
                    $c->get(MailerInterface::class),
                    $c->get(ResourceRepositoryInterface::class),
                    $c->get(UserRepository::class)
                )
            );

            $dispatcher->subscribe(
                BookingCancelled::class,
                new RemoveBookingFromCalendarHandler($calendarSync, $c->get(BookingRepositoryInterface::class))
            );

            return $dispatcher;
        });

        $container->bind(EventDispatcherInterface::class, fn (Container $c) => $c->get(InMemoryEventDispatcher::class));

        // ============================================================
        // Application - Use Cases
        // ============================================================

        $container->bind(CreateBooking::class, fn (Container $c) => new CreateBooking(
            $c->get(BookingRepositoryInterface::class),
            $c->get(TenantContextInterface::class),
            $c->get(EventDispatcherInterface::class),
            $c->get(CalendarSyncService::class)
        ));

        $container->bind(CancelBooking::class, function (Container $c) use ($getEnv): CancelBooking {
            $hours = (int) $getEnv('CANCELLATION_MIN_HOURS_BEFORE_START', '24');
            return new CancelBooking(
                $c->get(BookingRepositoryInterface::class),
                $c->get(TenantContextInterface::class),
                new CancellationPolicy($hours),
                $c->get(EventDispatcherInterface::class),
            );
        });

        $container->bind(CreateResource::class, fn (Container $c) => new CreateResource(
            $c->get(ResourceRepositoryInterface::class),
            $c->get(TenantContextInterface::class)
        ));

        $container->bind(LoginUser::class, fn (Container $c) => new LoginUser(
            $c->get(UserRepository::class),
            $c->get(JwtGeneratorInterface::class)
        ));

        // ============================================================
        // HTTP - Middleware
        // ============================================================

        $container->bind(AuthMiddleware::class, fn (Container $c) => new AuthMiddleware(
            $c->get(JwtValidatorInterface::class),
            $c->get(TenantContextInterface::class),
            $c->get(UserContextInterface::class),
            $c->get(UserRepository::class)
        ));

        // ============================================================
        // HTTP - Controllers
        // ============================================================

        $container->bind(BookingController::class, fn (Container $c) => new BookingController(
            $c->get(CreateBooking::class),
            $c->get(CancelBooking::class),
            $c->get(BookingRepositoryInterface::class),
            $c->get(CalendarSyncService::class),
            $c->get(EventDispatcherInterface::class)
        ));

        $container->bind(ResourceController::class, fn (Container $c) => new ResourceController(
            $c->get(ResourceRepositoryInterface::class),
            $c->get(CreateResource::class)
        ));

        $container->bind(AuthController::class, fn (Container $c) => new AuthController(
            $c->get(LoginUser::class),
            $c->get(UserContextInterface::class)
        ));

        $container->bind(GoogleAuthController::class, fn (Container $c) => new GoogleAuthController(
            $c->get(GoogleCalendarClient::class),
            $c->get(UserRepository::class)
        ));

        // ============================================================
        // HTTP - Router
        // ============================================================

        $container->singleton(Router::class, function (Container $c) {
            $router = new Router();

            // Public routes (protected = false)
            $router->post('/api/login', fn () => $c->get(AuthController::class), false);

            // Google Auth routes
            $router->get('/api/auth/google/url', fn () => $c->get(GoogleAuthController::class));
            $router->get('/api/auth/google/callback', fn () => $c->get(GoogleAuthController::class), false);

            // My Profile
            $router->get('/api/me', fn () => $c->get(AuthController::class));

            // Resource routes (protected)
            $router->get('/api/resources', fn () => $c->get(ResourceController::class));
            $router->post('/api/resources', fn () => $c->get(ResourceController::class));

            // Booking routes (protected)
            $router->get('/api/bookings', fn () => $c->get(BookingController::class));
            $router->post('/api/bookings/sync', fn () => $c->get(BookingController::class));
            $router->post('/api/bookings', fn () => $c->get(BookingController::class));
            $router->delete('/api/bookings/{id}', fn () => $c->get(BookingController::class));

            // Health check (public)
            $router->get('/health', fn () => null, false);

            return $router;
        });

        return $container;
    }
}
