---
name: authentication
description: User authentication, JWT token generation, and session management. Use when implementing login, logout, or protected routes.
---

# Authentication Skill

## Responsibilities
- Authenticate users (email/password, OAuth, etc.)
- Generate JWT tokens with tenant context
- Validate JWT tokens on protected routes
- Handle token refresh and expiration
- Manage user sessions

## Constraints
- Passwords must be hashed with bcrypt (cost 12+ in PHP 8.4)
- JWT tokens **MUST** include `tenant_id` claim
- Tokens should be short-lived (15-60 minutes)
- Refresh tokens stored securely (HTTP-only cookies or database)
- No sensitive data in JWT payload (it's base64, not encrypted)

## JWT Structure
```json
{
  "sub": "user-uuid",
  "email": "user@example.com",
  "tenant_id": "tenant-uuid",
  "roles": ["user"],
  "iat": 1234567890,
  "exp": 1234571490
}
```

## Implementation Pattern

### Login Flow
```php
final class LoginUser
{
    public function execute(LoginCommand $command): AuthToken
    {
        // 1. Find user by email
        $user = $this->users->findByEmail($command->email);
        if (!$user) {
            throw new InvalidCredentialsException();
        }
        
        // 2. Verify password
        if (!password_verify($command->password, $user->passwordHash())) {
            throw new InvalidCredentialsException();
        }
        
        // 3. Generate JWT with tenant_id
        $token = $this->jwtGenerator->generate([
            'sub' => $user->id(),
            'email' => $user->email(),
            'tenant_id' => $user->tenantId()->toString(),
            'roles' => $user->roles(),
        ]);
        
        return new AuthToken($token);
    }
}
```

### Protected Route Middleware
```php
final class AuthMiddleware
{
    public function handle(Request $request): void
    {
        $token = $request->bearerToken();
        if (!$token) {
            throw new UnauthorizedException();
        }
        
        $payload = $this->jwtValidator->validate($token);
        
        // Set tenant context from JWT
        $this->tenantContext->setTenantId(
            TenantId::fromString($payload['tenant_id'])
        );
        
        // Set authenticated user
        $request->setUser($payload);
    }
}
```

## Security Best Practices
- ✅ Use HTTPS only (no JWT over HTTP)
- ✅ Implement rate limiting on login endpoint
- ✅ Log failed login attempts
- ✅ Use secure random for token secrets
- ✅ Rotate JWT secrets periodically
- ❌ Never log JWT tokens
- ❌ Never store JWT in localStorage (XSS risk)
- ❌ Never accept `tenant_id` from login form

## Edge Cases
- **Password reset**: Generate time-limited, single-use tokens
- **Multi-tenant users**: User can belong to multiple tenants, must choose on login
- **Token expiration during request**: Return 401, client should refresh
- **Concurrent sessions**: Allow or deny based on business rules

## Testing
```php
public function testCannotLoginWithWrongPassword(): void
{
    $loginUser = new LoginUser($users, $jwtGenerator);
    
    $this->expectException(InvalidCredentialsException::class);
    
    $loginUser->execute(new LoginCommand(
        email: 'user@example.com',
        password: 'wrong-password'
    ));
}

public function testJwtIncludesTenantId(): void
{
    $token = $loginUser->execute($command);
    $payload = JWT::decode($token->value());
    
    $this->assertEquals('tenant-123', $payload['tenant_id']);
}
```

## Non-goals
- OAuth provider implementation (use third-party library)
- 2FA/MFA (separate security context)
- User registration (separate user-management context)
- Password complexity validation (separate policy)

## References
- [JWT.io](https://jwt.io)
- PHP `password_hash()` and `password_verify()`
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
