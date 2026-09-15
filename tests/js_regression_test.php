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

$studentHtml = file_get_contents(__DIR__ . '/../index.html');
$studentJs = file_get_contents(__DIR__ . '/../assets/app.js');
$studentCss = file_get_contents(__DIR__ . '/../assets/app.css');
if (!is_string($studentHtml) || !is_string($studentJs) || !is_string($studentCss)) {
    fwrite(STDERR, 'FAILED: could not read student UI files' . PHP_EOL);
    exit(1);
}

$pointsScriptPosition = strpos($studentHtml, 'assets/points.js');
$appScriptPosition = strpos($studentHtml, 'assets/app.js');
if ($pointsScriptPosition === false || $appScriptPosition === false || $pointsScriptPosition >= $appScriptPosition) {
    fwrite(STDERR, 'FAILED: student points helpers must load before app.js' . PHP_EOL);
    exit(1);
}

$requiredStudentUiFragments = [
    'StudentPoints.experimentRewardValue(experiment)',
    'role="progressbar"',
    'aria-valuemax="100"',
    'aria-valuenow="${summary.progressWidth}"',
    'aria-valuetext="${escapeHtml(summary.progressValueText)}"',
];
foreach ($requiredStudentUiFragments as $fragment) {
    if (!str_contains($studentJs, $fragment)) {
        fwrite(STDERR, 'FAILED: missing student progress fragment: ' . $fragment . PHP_EOL);
        exit(1);
    }
}

if (str_contains($studentJs, 'Number(experiment.creditedReward) < Number(experiment.rewardCredits)')) {
    fwrite(STDERR, 'FAILED: student reward rendering must not retain partial-credit wording' . PHP_EOL);
    exit(1);
}

if (!str_contains($studentCss, 'overflow: hidden;') || !str_contains($studentCss, 'max-width: 100%;')) {
    fwrite(STDERR, 'FAILED: student progress bar must be visually constrained to its track' . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'js_regression_test.php: ok' . PHP_EOL);
