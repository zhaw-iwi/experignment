<?php

declare(strict_types=1);

function environment_value(string $name, ?string $default = null): ?string
{
    $value = getenv($name);
    return is_string($value) ? $value : $default;
}

function environment_bool(string $name, ?bool $default = null): ?bool
{
    $value = environment_value($name);
    if ($value === null || trim($value) === '') {
        return $default;
    }

    return match (strtolower(trim($value))) {
        '1', 'true', 'yes', 'on' => true,
        '0', 'false', 'no', 'off' => false,
        default => throw new RuntimeException('Invalid boolean environment value for ' . $name . '.'),
    };
}

function load_environment_file(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('The environment file could not be read.');
    }

    foreach ($lines as $index => $line) {
        if ($index === 0) {
            $line = preg_replace('/^\xEF\xBB\xBF/', '', $line) ?? $line;
        }

        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }
        if (str_starts_with($trimmed, 'export ')) {
            $trimmed = trim(substr($trimmed, 7));
        }

        $separator = strpos($trimmed, '=');
        if ($separator === false) {
            throw new RuntimeException(sprintf('Invalid environment entry on line %d.', $index + 1));
        }

        $name = trim(substr($trimmed, 0, $separator));
        if (preg_match('/^[A-Z_][A-Z0-9_]*$/', $name) !== 1) {
            throw new RuntimeException(sprintf('Invalid environment name on line %d.', $index + 1));
        }

        if (getenv($name) !== false) {
            continue;
        }

        $value = parse_environment_value(substr($trimmed, $separator + 1), $index + 1);
        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function parse_environment_value(string $rawValue, int $lineNumber): string
{
    $value = trim($rawValue);
    if ($value === '') {
        return '';
    }

    $quote = $value[0];
    if ($quote !== '"' && $quote !== "'") {
        return $value;
    }
    if (strlen($value) < 2 || $value[strlen($value) - 1] !== $quote) {
        throw new RuntimeException(sprintf('Unclosed environment value on line %d.', $lineNumber));
    }

    $inner = substr($value, 1, -1);
    if ($quote === "'") {
        return str_replace(["\\'", '\\\\'], ["'", '\\'], $inner);
    }

    return strtr($inner, [
        '\\n' => "\n",
        '\\r' => "\r",
        '\\t' => "\t",
        '\\"' => '"',
        '\\\\' => '\\',
    ]);
}
