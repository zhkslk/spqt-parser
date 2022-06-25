<?php

/**
 * Filtering each line
 *
 * @param string $line
 * @return bool
 */
function shouldSkipLine(string $line): bool {
    $line = trim($line);

    // skip empty line
    if (empty($line)) {
        return true;
    }

    // if it's a comment - skip line (excluding ##### inline comment)
    if (substr($line, 0, 1) == '#' && substr($line, 0, 5) != '#####') {
        return true;
    }

    // assure key value pair
    if (count($keyValue = explode('=', $line)) != 2) {
        return true;
    }

    // assure key value pair is not empty
    if (strlen(trim($keyValue[0])) == 0 || strlen(trim($keyValue[1])) == 0) {
        return true;
    }

    return false;
}

/**
 * Parse file line by line
 *
 * @param string $filePath
 * @return array
 */
function getFileLines(string $filePath): array {
    $file = fopen($filePath, "r");
    if ( ! $file) {
        die("File cannot be opened.");
    }

    $lines = [];
    while( ! feof($file)) {
        $line = fgets($file);
        if (shouldSkipLine($line)) {
            continue;
        }
        $lines[] = $line;
    }
    fclose($file);

    return $lines;
}

/**
 * Format value to its proper type
 *
 * @param string $value
 * @return bool|int|string
 */
function getFormattedValue(string $value) {
    $value = trim($value);
    $value = trim($value, '"');

    if ($value == 'true') {
        return true;
    }

    if ($value == 'false') {
        return false;
    }

    if (is_numeric($value)) {
        return (int) $value;
    }

    return $value;
}

/**
 * Build multidimensional array
 *
 * @param array $keys
 * @param string $value
 * @return array
 */
function transform(array $keys, string $value): array
{
    $temp = [];
    $key = array_shift($keys);
    if (!isset($keys[0])) {
        $temp[$key] = getFormattedValue($value);
    } else {
        $temp[$key] = transform($keys, $value);
    }
    return $temp;
}

/**
 * Parse file and build configs multi-array
 *
 * @param string $file
 * @return array
 */
function parseConfigsFromFile(string $file): array {
    $lines = getFileLines($file);

    $configs = [];
    foreach ($lines as $line) {
        list($key, $value) = explode('=', $line);
        $key = trim($key);
        $value = trim($value);

        // filter inline-comment
        $key = explode(' ', $key);
        $key = array_pop($key);

        $fullKeys = explode('.', $key);
        $configs = array_merge_recursive($configs, transform($fullKeys, $value));
    }

    return $configs;
}

$configs = parseConfigsFromFile(__DIR__ . "/config.txt");

var_dump($configs);
