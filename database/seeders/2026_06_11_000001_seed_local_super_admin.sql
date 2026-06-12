INSERT INTO users (
    uuid,
    full_name,
    email,
    phone,
    password_hash,
    role,
    tent_id,
    status
) VALUES (
    UUID(),
    'Local Super Admin',
    'admin@example.test',
    NULL,
    '$2y$10$MrO07oASj7G0CZKo1L3yLOvpkGQTBpBOWvEkdmjiD/t/EdbLBzU6e',
    'Super Admin',
    NULL,
    'active'
)
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    password_hash = VALUES(password_hash),
    role = VALUES(role),
    status = VALUES(status);
