<?php

declare(strict_types=1);

$manageJsPath = __DIR__ . '/../manage/manage.js';
$content = file_get_contents($manageJsPath);

if (!is_string($content)) {
    fwrite(STDERR, 'FAILED: could not read manage/manage.js' . PHP_EOL);
    exit(1);
}

if (preg_match('/function renderPoolSection\(\) \{(?P<body>.*?)\n\}/s', $content, $matches) !== 1) {
    fwrite(STDERR, 'FAILED: renderPoolSection not found' . PHP_EOL);
    exit(1);
}

if (str_contains($matches['body'], 'visibleRows')) {
    fwrite(STDERR, 'FAILED: renderPoolSection must not reference grading visibleRows' . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'js_regression_test.php: ok' . PHP_EOL);
