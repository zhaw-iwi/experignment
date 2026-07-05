<?php

declare(strict_types=1);

/**
 * Checks user-facing text for:
 * - transliterated umlaut forms (ae/oe/ue variants)
 * - mojibake artifacts
 */

$htmlFiles = [
    __DIR__ . '/../index.html',
    __DIR__ . '/../manage/index.html',
];

$codeFiles = [
    __DIR__ . '/../assets/app.js',
    __DIR__ . '/../manage/manage.js',
    __DIR__ . '/../api/student_overview.php',
    __DIR__ . '/../api/claim.php',
    __DIR__ . '/../api/choose_slot.php',
    __DIR__ . '/../api/_bootstrap.php',
    __DIR__ . '/../api/manage/actions.php',
    __DIR__ . '/../api/manage/dashboard.php',
    __DIR__ . '/../api/manage/report.php',
    __DIR__ . '/../api/manage/search_students.php',
    __DIR__ . '/../api/manage/reset_student.php',
    __DIR__ . '/../api/manage/add_allowed_student.php',
];

$transliterationPattern = '/\b(?:fuer|gueltig|ungueltig|gewaehlt|waehlen|zurueck[\p{L}]*|loesch[\p{L}]*|hinzufueg[\p{L}]*|verfueg[\p{L}]*|oeffn[\p{L}]*|pruef[\p{L}]*|schliess[\p{L}]*|bestaetig[\p{L}]*|ausfuehr[\p{L}]*)\b/iu';
$mojibakePattern = '/(?:Ã.|Â.|�|â€|â€™|â€œ|â€�|â€“|â€¦)/u';

$violations = [];

foreach ($htmlFiles as $filePath) {
    $content = file_get_contents($filePath);
    if (!is_string($content)) {
        $violations[] = 'FAILED to read file: ' . $filePath;
        continue;
    }

    $fragments = [];

    if (preg_match_all('/>([^<>]+)</u', $content, $textMatches) === 1 || !empty($textMatches[1])) {
        foreach ($textMatches[1] as $fragment) {
            $fragments[] = html_entity_decode(trim($fragment), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }

    if (preg_match_all('/(?:placeholder|aria-label|title)\s*=\s*"([^"]*)"/u', $content, $attrMatches) === 1 || !empty($attrMatches[1])) {
        foreach ($attrMatches[1] as $fragment) {
            $fragments[] = html_entity_decode(trim($fragment), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }

    foreach ($fragments as $fragment) {
        if ($fragment === '') {
            continue;
        }

        if (preg_match($transliterationPattern, $fragment) === 1) {
            $violations[] = sprintf('%s: transliteration detected in "%s"', $filePath, $fragment);
        }

        if (preg_match($mojibakePattern, $fragment) === 1) {
            $violations[] = sprintf('%s: mojibake detected in "%s"', $filePath, $fragment);
        }
    }
}

foreach ($codeFiles as $filePath) {
    $content = file_get_contents($filePath);
    if (!is_string($content)) {
        $violations[] = 'FAILED to read file: ' . $filePath;
        continue;
    }

    if (preg_match_all('/\'((?:\\\\.|[^\'\\\\])*)\'|"((?:\\\\.|[^"\\\\])*)"/s', $content, $matches, PREG_SET_ORDER) !== false) {
        foreach ($matches as $match) {
            $singleQuoted = isset($match[1]) ? (string) $match[1] : '';
            $doubleQuoted = isset($match[2]) ? (string) $match[2] : '';
            $raw = $singleQuoted !== '' ? $singleQuoted : $doubleQuoted;
            $fragment = stripcslashes($raw);
            $fragment = trim($fragment);

            if ($fragment === '') {
                continue;
            }

            // Keep focus on user-visible messages rather than technical literals.
            if (preg_match('/\s/u', $fragment) !== 1) {
                continue;
            }

            if (preg_match('/\b(?:SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|VALUES|INNER JOIN|LIMIT|SET|ORDER BY)\b/', $fragment) === 1) {
                continue;
            }

            if (preg_match($transliterationPattern, $fragment) === 1) {
                $violations[] = sprintf('%s: transliteration detected in "%s"', $filePath, $fragment);
            }

            if (preg_match($mojibakePattern, $fragment) === 1) {
                $violations[] = sprintf('%s: mojibake detected in "%s"', $filePath, $fragment);
            }
        }
    }
}

if ($violations !== []) {
    foreach ($violations as $violation) {
        fwrite(STDERR, 'FAILED: ' . $violation . PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, 'text_quality_test.php: ok' . PHP_EOL);
