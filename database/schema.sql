-- BookFlow Database Schema

CREATE TABLE IF NOT EXISTS bookings (
    id VARCHAR(50) PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL,
    resource_id VARCHAR(50) NOT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    status ENUM('confirmed', 'cancelled', 'pending') NOT NULL DEFAULT 'confirmed',
    google_event_id VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    
    INDEX idx_tenant_bookings (tenant_id, starts_at),
    INDEX idx_resource_time (resource_id, starts_at, ends_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resources (
    id VARCHAR(50) PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at DATETIME NOT NULL,
    
    INDEX idx_tenant_resources (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(50) PRIMARY KEY,
    tenant_id VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    google_auth_data JSON DEFAULT NULL,
    created_at DATETIME NOT NULL,

    UNIQUE KEY uk_email (email),
    INDEX idx_tenant_users (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data for testing
INSERT INTO resources (id, tenant_id, name, description, created_at) VALUES
('resource-1', 'tenant-demo', 'Conference Room A', 'Main conference room with projector', NOW()),
('resource-2', 'tenant-demo', 'Conference Room B', 'Small meeting room', NOW()),
('resource-3', 'tenant-demo', 'Desk 1', 'Hot desk in open area', NOW()),
('resource-4', 'tenant-demo', 'Desk 2', 'Hot desk in open area', NOW()),
('resource-5', 'tenant-demo', 'Focus Room', 'Soundproof pod for calls', NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Seed user (password: password)
INSERT INTO users (id, tenant_id, email, password_hash, name, created_at) VALUES
('user-1', 'tenant-demo', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name);
