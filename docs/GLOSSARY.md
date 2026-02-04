# Domain Glossary

## Booking
A reserved time interval for a specific resource.

## Resource
An entity that can be booked (person, room, equipment).

## Availability
Rules defining when a resource can be booked.

## Tenant
An isolated organization using the system.

## Tenant Context
The mechanism for resolving which tenant is making the current request.

## Value Object
An immutable object defined by its attributes (e.g., `TenantId`, `DateRange`).

## Domain Event
A record of something that happened in the domain (e.g., `BookingCreated`).

## Background Job
An asynchronous task executed outside the HTTP request cycle.
