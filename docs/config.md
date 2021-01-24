# Configuration

For customize settings you just needs to create a file named `.changelog` on the root of your project/ on the working
dir or use the `--config` option to specify the location of your configuration file.

> **Notes:**<br>
> - When a setting on the configuration file is not necessary just omit it
> - To allow all types just keep empty `types` and set empty `ignoreTypes`

## Settings

- **Root:** Working directory of your project
- **Path:** Path to your changelog file (relative to the working dir/root)
- **Header Title:** Header title of the changelog
- **Header Description:** Header subtitle of the changelog
- **Preset:** Add new types preset or modify existing types preset labels and description
- **Types:** Types allowed and showed on changelog. This setting could overwrite ignored types.
- **Ignore Types:** Types ignored and so hidden on changelog
- **Ignore Patterns:** Patterns ignored and so hidden on changelog with a specific description. *(Regex are enabled)*

### Default settings

These are the default settings:

```php
<?php

return [
  'root' => __DIR__,
  'path' => 'CHANGELOG.md',
  'headerTitle' => 'Changelog',
  'headerDescription' => 'All notable changes to this project will be documented in this file.',
  'preset' => [
    // Breaking changes section
    'breaking_changes' => ['label' => '⚠ BREAKING CHANGES', 'description' => 'Code changes that potentially causes other components to fail'],
    // Types section
    'feat'     => ['label' => 'Features', 'description' => 'New features'],
    'perf'     => ['label' => 'Performance Improvements', 'description' => 'Code changes that improves performance'],
    'fix'      => ['label' => 'Bug Fixes', 'description' => 'Bugs and issues resolution'],
    'refactor' => ['label' => 'Code Refactoring', 'description' => 'A code change that neither fixes a bug nor adds a feature'],
    'style'    => ['label' => 'Styles', 'description' => 'Changes that do not affect the meaning of the code'],
    'test'     => ['label' => 'Tests', 'description' => 'Adding missing tests or correcting existing tests'],
    'build'    => ['label' => 'Builds', 'description' => 'Changes that affect the build system or external dependencies '],
    'ci'       => ['label' => 'Continuous Integrations', 'description' => 'Changes to CI configuration files and scripts'],
    'docs'     => ['label' => 'Documentation', 'description' => 'Documentation changes'],
    'chore'    => ['label' => 'Chores', 'description' => "Other changes that don't modify the source code or test files"],
    'revert'   => ['label' => 'Reverts', 'description' => 'Reverts a previous commit'],
  ],
  'types' => [],
  'ignoreTypes' => ['build', 'chore', 'ci', 'docs', 'perf', 'refactor', 'revert', 'style', 'test'],
  'ignorePatterns' => ['/^chore\(release\):/i'],
];
```

## Examples

Configure your preferences with the help of the following examples.

#### Short Example

```php
<?php

return [
    // Types allowed on changelog
    'types' => ['feat', 'fix', 'perf', 'docs', 'chore'],
    // Ignore chores with changelogs scope
    'ignorePatterns' => [
        '/chore\(changelog\)[:].*/i'
    ],
];
```

#### Full Example

```php
<?php

return [
  'root' => dirname(__DIR__), // (ex. configs/changelog.php using --config option)
  // File changelog (relative to the working dir/root)
  'path' => 'docs/CHANGELOG.md', // You can specify a different folder
  'headerTitle' => 'My changelog',
  'headerDescription' => 'This is my changelog file.',
  'preset' => [
    // Add improvements type (deprecated type)
    'improvements' => [
      'label' => 'Improvements',
      'description' => 'Improvements to existing features'
    ],
    'chore' => [
      // Change chore default label
      'label' => 'Others'
    ],
  ],
   // Types allowed on changelog
  'types' => ['feat', 'fix', 'pref'], // These could overwrite ignored types
  // Exclude not notables types (following types are the default excluded types)
  'ignoreTypes' => ['build', 'chore', 'ci', 'docs', 'refactor', 'revert', 'style', 'test'],
  'ignorePatterns' => [
    // Exclude all commits with this specific description
    'chore(deps): update dependencies',
    // You can also use regex to exclude all commit like 'chore(changelog): updated'
    '/chore\(changelog\)[:].*/i'
  ],
];
```
