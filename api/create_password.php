<?php
echo "Password 'admin123' hash: " . password_hash('admin123', PASSWORD_DEFAULT) . "\n";
echo "Password 'student123' hash: " . password_hash('student123', PASSWORD_DEFAULT) . "\n";
?>