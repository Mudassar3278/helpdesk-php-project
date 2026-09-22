-- ================================================================
-- Helpdesk / Support Ticket System - Database Schema + Seed Data
-- Engine: MySQL / MariaDB
-- Import this file via phpMyAdmin or: mysql -u root < database.sql
-- ================================================================

CREATE DATABASE IF NOT EXISTS helpdesk_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE helpdesk_system;

-- ---------------------------------------------------------------
-- ROLES
-- ---------------------------------------------------------------
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (name) VALUES ('admin'), ('agent'), ('customer');

-- ---------------------------------------------------------------
-- PERMISSIONS
-- Master list of every controllable action in the system.
-- ---------------------------------------------------------------
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(150) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'General'
);

INSERT INTO permissions (key_name, label, category) VALUES
('users_view',           'View users list',                    'User Management'),
('users_add',            'Add new user',                        'User Management'),
('users_edit',           'Edit user (role / status / info)',    'User Management'),
('users_delete',         'Delete user',                          'User Management'),
('tickets_view_all',     'View ALL tickets (system-wide)',       'Ticket Access'),
('tickets_view_assigned','View tickets assigned to me',          'Ticket Access'),
('tickets_view_own',     'View tickets I created',               'Ticket Access'),
('tickets_create',       'Create a new ticket',                  'Ticket Actions'),
('tickets_reply',        'Reply / comment on a ticket',          'Ticket Actions'),
('tickets_assign',       'Assign a ticket to an agent',          'Ticket Actions'),
('tickets_change_status','Change ticket status',                 'Ticket Actions'),
('tickets_delete',       'Delete a ticket',                       'Ticket Actions');

-- ---------------------------------------------------------------
-- ROLE_PERMISSIONS
-- This is the table the Admin controls from admin/permissions.php.
-- The "admin" role is a built-in superuser and always bypasses this
-- table in code (see includes/functions.php -> hasPermission()),
-- so normally only "agent" and "customer" rows get edited here.
-- ---------------------------------------------------------------
CREATE TABLE role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_role_permission (role_id, permission_id)
);

-- Default permissions for Agent (role_id = 2)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE key_name IN ('tickets_view_assigned','tickets_reply','tickets_change_status');

-- Default permissions for Customer (role_id = 3)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE key_name IN ('tickets_create','tickets_view_own','tickets_reply');

-- ---------------------------------------------------------------
-- USERS
-- ---------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

-- ---------------------------------------------------------------
-- TICKETS
-- ---------------------------------------------------------------
CREATE TABLE tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('Technical','Billing','General','Other') NOT NULL DEFAULT 'General',
    priority ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
    status ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
    created_by INT NOT NULL,      -- the customer who raised it
    assigned_to INT NULL,          -- the agent handling it (nullable = unassigned)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- ---------------------------------------------------------------
-- TICKET_REPLIES (conversation thread on a ticket)
-- ---------------------------------------------------------------
CREATE TABLE ticket_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------
-- SEED USERS
-- Plain-text passwords below are for YOUR reference only, the
-- password column always stores a bcrypt hash via password_hash().
-- admin@demo.com    -> admin123
-- agent@demo.com    -> agent123
-- customer@demo.com -> customer123
-- ---------------------------------------------------------------
INSERT INTO users (full_name, email, password, role_id, status) VALUES
('System Admin',  'admin@demo.com',    '$2y$10$I9WsTTByUDuvddCDTzkyGOn1tjwVnbU8TrT0w2391QUI4/Bp3Sh32', 1, 'active'),
('Bilal Agent',   'agent@demo.com',    '$2y$10$eeeV/RewfvZ6Mlo.CdArVuDAcMn1pXFp/.c1zyrmWc7wbmo2elOVG', 2, 'active'),
('Hina Customer', 'customer@demo.com', '$2y$10$THtzzpvrm8V7h1VHiP8NE.MmdT25xmPDXKblv1hwTZiP0OoZ/9BVa', 3, 'active');

-- ---------------------------------------------------------------
-- SEED TICKETS + REPLIES
-- ---------------------------------------------------------------
INSERT INTO tickets (subject, description, category, priority, status, created_by, assigned_to) VALUES
('Cannot login to my account', 'I get an "invalid password" error even after resetting it.', 'Technical', 'High', 'in_progress', 3, 2),
('Invoice amount is wrong', 'My last invoice shows double charge for the same plan.', 'Billing', 'Urgent', 'open', 3, NULL);

INSERT INTO ticket_replies (ticket_id, user_id, message) VALUES
(1, 3, 'Tried resetting my password twice, still the same issue.'),
(1, 2, 'Looking into it now, can you tell me which browser you are using?');
