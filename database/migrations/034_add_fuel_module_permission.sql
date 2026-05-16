INSERT INTO role_permissions (role_id, permission_code)
SELECT r.id, 'fuel.manage'
FROM roles r
WHERE r.code IN ('admin', 'all_modules')
ON DUPLICATE KEY UPDATE permission_code = VALUES(permission_code);
