<?php

$tag = $argv[1] ?? '';
$changelogPath = $argv[2] ?? 'CHANGELOG.md';

if (!preg_match('/^v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$/D', $tag)) {
    fwrite(STDERR, "Expected a stable vMAJOR.MINOR.PATCH tag.\n");
    exit(1);
}

$changelog = @file_get_contents($changelogPath);
if ($changelog === false) {
    fwrite(STDERR, "Cannot read the changelog.\n");
    exit(1);
}

$version = preg_quote(substr($tag, 1), '/');
$pattern = '/^## \[' . $version . '\]\([^\r\n]+\)[^\r\n]*\R(.*?)(?=^## |\z)/ms';
if (!preg_match($pattern, $changelog, $matches)) {
    fwrite(STDERR, "No changelog section found for $tag.\n");
    exit(1);
}

$notes = trim(preg_replace('/\R---\s*$/', '', $matches[1]));
if ($notes === '') {
    fwrite(STDERR, "The release notes are empty.\n");
    exit(1);
}

echo $notes . PHP_EOL;
