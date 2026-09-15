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
$chestJs = file_get_contents(__DIR__ . '/../assets/chests.js');
$studentCss = file_get_contents(__DIR__ . '/../assets/app.css');
if (!is_string($studentHtml) || !is_string($studentJs) || !is_string($chestJs) || !is_string($studentCss)) {
    fwrite(STDERR, 'FAILED: could not read student UI files' . PHP_EOL);
    exit(1);
}

$pointsScriptPosition = strpos($studentHtml, 'assets/points.js');
$chestScriptPosition = strpos($studentHtml, 'assets/chests.js');
$appScriptPosition = strpos($studentHtml, 'assets/app.js');
if (
    $pointsScriptPosition === false
    || $chestScriptPosition === false
    || $appScriptPosition === false
    || $pointsScriptPosition >= $chestScriptPosition
    || $chestScriptPosition >= $appScriptPosition
) {
    fwrite(STDERR, 'FAILED: student points and chest helpers must load before app.js' . PHP_EOL);
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

$requiredChestHtmlFragments = [
    'id="studentChestInbox"',
    'id="studentChestModal"',
    'id="studentChestOpenButton"',
    'class="chest-particles" aria-hidden="true"',
    'src="assets/chests/chest-closed.png"',
    'alt=""',
    'role="status" aria-live="polite"',
];
foreach ($requiredChestHtmlFragments as $fragment) {
    if (!str_contains($studentHtml, $fragment)) {
        fwrite(STDERR, 'FAILED: missing semantic chest markup: ' . $fragment . PHP_EOL);
        exit(1);
    }
}

if (preg_match('/<button\s+[^>]*id="studentChestOpenButton"[^>]*type="button"[^>]*>/s', $studentHtml) !== 1) {
    fwrite(STDERR, 'FAILED: the chest opening control must be a native button' . PHP_EOL);
    exit(1);
}

if (preg_match('/<div class="chest-particles"[^>]*>(?P<particles>.*?)<\/div>/s', $studentHtml, $particleMatches) !== 1
    || substr_count($particleMatches['particles'], '<span') !== 14
) {
    fwrite(STDERR, 'FAILED: chest markup must contain exactly 14 deterministic particles' . PHP_EOL);
    exit(1);
}

$requiredChestControllerFragments = [
    'charge: 700',
    'reveal: 120',
    'settle: 360',
    'particleTail: 430',
    'window.matchMedia("(prefers-reduced-motion: reduce)")',
    'this.phase = "charging"',
    'this.elements.openButton.disabled = true',
    'this.animationToken',
    'this.timers',
    'isCurrentPresentation(eventId, presentationToken)',
    'this.elements.body.textContent',
    'assets/chests/chest-open-gold.png',
];
foreach ($requiredChestControllerFragments as $fragment) {
    if (!str_contains($chestJs, $fragment)) {
        fwrite(STDERR, 'FAILED: missing chest controller invariant: ' . $fragment . PHP_EOL);
        exit(1);
    }
}

if (str_contains($chestJs, 'innerHTML')) {
    fwrite(STDERR, 'FAILED: chest event content must not be inserted as HTML' . PHP_EOL);
    exit(1);
}

$requiredChestCssFragments = [
    '.chest-stage.is-charging::before',
    '.chest-stage.is-bursting::after',
    'animation: chestPressure 700ms',
    'animation: chestShockwave 600ms',
    'animation: chestStageRecoil 300ms',
    'animation: chestParticleBurst 900ms',
    '.chest-particles span:nth-child(14)',
    '@media (prefers-reduced-motion: reduce)',
];
foreach ($requiredChestCssFragments as $fragment) {
    if (!str_contains($studentCss, $fragment)) {
        fwrite(STDERR, 'FAILED: missing chest choreography fragment: ' . $fragment . PHP_EOL);
        exit(1);
    }
}

$requiredChestIntegrationFragments = [
    'loadOverview(true)',
    'loadOverview(false)',
    'api/student_chests.php',
    'api/open_student_chest.php',
    'chestController?.cancelPresentation()',
    'restoreFocusAfterChest()',
];
foreach ($requiredChestIntegrationFragments as $fragment) {
    if (!str_contains($studentJs, $fragment)) {
        fwrite(STDERR, 'FAILED: missing student chest integration fragment: ' . $fragment . PHP_EOL);
        exit(1);
    }
}

fwrite(STDOUT, 'js_regression_test.php: ok' . PHP_EOL);
