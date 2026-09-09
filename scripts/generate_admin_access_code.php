<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, 'This helper can only run from the command line.' . PHP_EOL);
    exit(1);
}

$letters = 'abcdefghjkmnpqrstuvwxyz';
$digits = '23456789';
$alphabet = $letters . $digits;
$characters = [
    $letters[random_int(0, strlen($letters) - 1)],
    $digits[random_int(0, strlen($digits) - 1)],
];

while (count($characters) < 20) {
    $characters[] = $alphabet[random_int(0, strlen($alphabet) - 1)];
}

for ($index = count($characters) - 1; $index > 0; $index--) {
    $other = random_int(0, $index);
    [$characters[$index], $characters[$other]] = [$characters[$other], $characters[$index]];
}

$accessCode = implode('', $characters);
$hash = password_hash($accessCode, PASSWORD_DEFAULT);

fwrite(STDOUT, 'Administrator access code (shown once): ' . $accessCode . PHP_EOL);
fwrite(STDOUT, 'ADMIN_ACCESS_CODE_HASH=' . $hash . PHP_EOL);
