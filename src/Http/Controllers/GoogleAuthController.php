<?php

declare(strict_types=1);

namespace BookFlow\Http\Controllers;

use BookFlow\Application\Shared\Interfaces\TenantContextInterface;
use BookFlow\Application\Shared\Interfaces\UserContextInterface;
use BookFlow\Domain\User\UserRepository;
use BookFlow\Http\Response;
use BookFlow\Infrastructure\Calendar\GoogleCalendarClient;

/**
 * Controller for Google OAuth flow.
 */
final class GoogleAuthController
{
    public function __construct(
        private GoogleCalendarClient $googleClient,
        private UserRepository $userRepo,
        private TenantContextInterface $tenantContext,
        private ?UserContextInterface $userContext = null
    ) {
    }

    /**
     * Get the URL to redirect the user to Google for authentication.
     */
    public function getAuthUrl(): array
    {
        return ['url' => $this->googleClient->getAuthUrl()];
    }

    /**
     * Handle the callback from Google after user authentication.
     */
    public function callback(array $params): void
    {
        if (!isset($params['code'])) {
            // In a real app, we would redirect to an error page or show a message
            Response::error('Missing auth code', 400)->send();
            exit;
        }

        try {
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($params['code']);

            // For demonstration, we assume we are connecting the admin user of the current tenant.
            // In a production app, the user_id would be extracted from the authenticated session/JWT.
            $user = $this->userRepo->findByEmail('admin@example.com');

            if ($user) {
                $user->connectGoogle($token);
                $this->userRepo->save($user);
            } else {
                Response::error('Admin user not found for sync connection', 404)->send();
                exit;
            }

            // Redirect back to frontend
            header('Location: /frontend/public/index.html?sync=success');
            exit;
        } catch (\Exception $e) {
            error_log('Google Auth Error: ' . $e->getMessage());
            Response::error('Google authentication failed: ' . $e->getMessage(), 500)->send();
            exit;
        }
    }
}
