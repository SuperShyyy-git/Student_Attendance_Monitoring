<?php
// Test if the hash matches "machine123"
$hash = "$2y$10$hp1Bap4DcFiYGhN3ghYdQOgJhznkwf68OFOAQUZLFCBgsEbIf4jTi";
$password = "machine123";

if (password_verify($password, $hash)) {
    echo "✓ Hash is VALID for password: machine123<br>";
    echo "The hash matches!<br>";
} else {
    echo "✗ Hash is INVALID for password: machine123<br>";
    echo "The hash does NOT match!<br>";
}

echo "<br><br>";
echo "Generated hash for 'machine123': " . password_hash("machine123", PASSWORD_DEFAULT) . "<br>";
?>
