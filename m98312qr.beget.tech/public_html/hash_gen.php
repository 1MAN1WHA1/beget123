<?php
// hash_gen.php
$password = 'admin123';
echo password_hash($password, PASSWORD_DEFAULT);
?>