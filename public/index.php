<?php

declare(strict_types=1);

date_default_timezone_set('UTC');

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
}

use BookFlow\Domain\Booking\Exception\BookingConflictException;
use BookFlow\Domain\Booking\Exception\BookingNotFoundException;
use BookFlow\Domain\Booking\Exception\CancellationNotAllowedException;
use BookFlow\Http\Controllers\AuthController;
use BookFlow\Http\Controllers\BookingController;
use BookFlow\Http\Controllers\GoogleAuthController;
use BookFlow\Http\Controllers\ResourceController;
use BookFlow\Http\Exception\HttpException;
use BookFlow\Http\Exception\NotFoundException;
use BookFlow\Http\Exception\UnauthorizedException;
use BookFlow\Http\Exception\ValidationException;
use BookFlow\Http\Middleware\AuthMiddleware;
use BookFlow\Http\Request;
use BookFlow\Http\Response;
use BookFlow\Http\Router;
use BookFlow\Infrastructure\Container;
use BookFlow\Infrastructure\ContainerConfig;

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Build container
    $container = new Container();
    ContainerConfig::configure($container);

    // Create request from globals
    $request = Request::fromGlobals();

    // Get router and auth middleware
    $router = $container->get(Router::class);
    $authMiddleware = $container->get(AuthMiddleware::class);

    // Match route
    $match = $router->match($request);

    if ($match === null) {
        // Handle health check separately for now
        if ($request->path === '/health') {
            Response::json(['status' => 'ok', 'timestamp' => time()])->send();
            exit;
        }

        // Redirect root to frontend
        if ($request->path === '/') {
            header('Location: /frontend/public/index.html');
            exit;
        }

        throw NotFoundException::route($request->path);
    }

    $route = $match['route'];
    $params = $match['params'];

    // Apply authentication for protected routes
    if ($route->protected) {
        $authMiddleware->handle($request);
    }

    // Resolve controller
    $controller = ($route->handler)();

    // Dispatch to appropriate method based on HTTP method and path
    $response = match (true) {
        // Auth
        $controller instanceof AuthController && $request->method === 'GET' && str_contains($request->path, '/me') =>
        $controller->me(),
        $controller instanceof AuthController && $request->method === 'POST' =>
        $controller->login($request->getJson()),

        // Google Auth
        $controller instanceof GoogleAuthController && $request->method === 'GET' && str_contains($request->path, '/url') =>
        $controller->getAuthUrl(),
        $controller instanceof GoogleAuthController && $request->method === 'GET' && str_contains($request->path, '/callback') =>
        $controller->callback($request->query),

        // Resources
        $controller instanceof ResourceController && $request->method === 'GET' =>
        $controller->index(),
        $controller instanceof ResourceController && $request->method === 'POST' =>
        $controller->store($request->getJson()),

        // Bookings
        $controller instanceof BookingController && $request->method === 'GET' =>
        $controller->index(),
        $controller instanceof BookingController && $request->method === 'POST' && str_contains($request->path, '/sync') =>
        $controller->syncWithExternalCalendar(),
        $controller instanceof BookingController && $request->method === 'POST' =>
        $controller->store($request->getJson()),
        $controller instanceof BookingController && $request->method === 'DELETE' =>
        $controller->destroy($params['id']),

        // Health check
        $controller === null && $request->path === '/health' =>
        ['status' => 'ok', 'timestamp' => time()],

        default => throw NotFoundException::route($request->path)
    };

    Response::json($response)->send();

} catch (ValidationException $e) {
    Response::validationError($e->getMessage(), $e->errors)->send();
} catch (UnauthorizedException $e) {
    Response::unauthorized($e->getMessage())->send();
} catch (NotFoundException | BookingNotFoundException $e) {
    Response::notFound($e->getMessage())->send();
} catch (BookingConflictException $e) {
    Response::json(['error' => $e->getMessage()], 409)->send();
} catch (CancellationNotAllowedException $e) {
    Response::json(['error' => $e->getMessage()], 422)->send();
} catch (HttpException $e) {
    Response::json($e->toArray(), $e->statusCode)->send();
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    Response::error('Database connection failed', 500)->send();
} catch (Throwable $e) {
    error_log('Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    Response::error('Server Error: ' . $e->getMessage(), 500)->send();
}
