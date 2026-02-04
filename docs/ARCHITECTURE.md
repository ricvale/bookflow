# System Architecture

## Overview
BookFlow follows a strict **Domain-Driven Design (DDD)** approach with a **Layered Architecture**.

## Layers

### 1. Domain Layer (`src/Domain`)
- **Responsibility**: Pure business logic and rules.
- **Dependencies**: None. Pure PHP.
- **Components**: Entities, Value Objects, Domain Events, Repository Interfaces.

### 2. Application Layer (`src/Application`)
- **Responsibility**: Orchestrates domain objects to fulfill use cases.
- **Dependencies**: Domain Layer.
- **Components**: Use Cases (Commands/Queries), DTOs, Ports.

### 3. Infrastructure Layer (`src/Infrastructure`)
- **Responsibility**: Implements interfaces defined in Domain/Application layers.
- **Dependencies**: Application, Domain, External Libraries.
- **Components**: Database Repositories, API Clients, Logging.

### 4. HTTP Layer (`src/Http`)
- **Responsibility**: Handles HTTP requests and responses.
- **Dependencies**: Application Layer.
- **Components**: Controllers, Middleware, Request/Response objects.

## Multi-Tenancy
- **Strategy**: Shared Database, Shared Schema.
- **Enforcement**: `tenant_id` column on all tenant-specific tables.
- **Security**: `TenantContext` must be resolved before any domain action.