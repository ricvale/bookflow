# Tenancy Skill

## Context
Enforces strict data isolation between tenants.

## Key Constraints
1. **Isolation**: Data from one tenant must NEVER be visible to another.
2. **Resolution**: Tenant identity is resolved from the authenticated user (JWT), never from input parameters.
3. **Scope**: All database queries must include a `WHERE tenant_id = ?` clause.

## Data Model
- `tenant_id`: UUID (Foreign Key on all tenant-scoped tables)