# Authentication Skill

## Context
Manages user identity and access control.

## Key Constraints
1. **Stateless**: Uses JWT (JSON Web Tokens) for authentication.
2. **Security**: Passwords must be hashed using Argon2id.
3. **Session**: No server-side sessions; state is contained in the token.

## Data Model
- `user_id`: UUID
- `email`: String (Unique per tenant)
- `password_hash`: String