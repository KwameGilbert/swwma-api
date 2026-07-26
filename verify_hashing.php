<?php

// Test script to verify password hashing and verification compatibility

$password = 'TestPassword123';

// 1. Test Argon2id (Old Hash Style)
// Manually created hash using the old settings to simulate an existing user
$argon2Hash = password_hash($password, PASSWORD_DEFAULT);

echo "Argon2id Hash: " . $argon2Hash . "\n";
echo "Verify Argon2id: " . (password_verify($password, $argon2Hash) ? "SUCCESS" : "FAILED") . "\n";

// 2. Test PASSWORD_DEFAULT (New Hash Style - Bcrypt)
$defaultHash = password_hash($password, PASSWORD_DEFAULT);

echo "Default Hash: " . $defaultHash . "\n";
echo "Verify Default: " . (password_verify($password, $defaultHash) ? "SUCCESS" : "FAILED") . "\n";

// 3. Verify that we can identify if a password needs rehash
echo "Needs Rehash (Argon2id Hash): " . (password_needs_rehash($argon2Hash, PASSWORD_DEFAULT) ? "YES" : "NO") . "\n";
echo "Needs Rehash (Default Hash): " . (password_needs_rehash($defaultHash, PASSWORD_DEFAULT) ? "YES" : "NO") . "\n";

if (password_verify($password, $argon2Hash) && password_verify($password, $defaultHash)) {
    echo "\n✅ VERIFICATION COMPLETE: password_verify correctly handles both hash formats.\n";
} else {
    echo "\n❌ VERIFICATION FAILED: One of the hashes could not be verified.\n";
}
