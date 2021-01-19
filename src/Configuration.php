<?php

namespace ConventionalChangelog;

class Configuration
{
    /**
     * Changelog filename.
     *
     * @var string
     */
    public $fileName = 'CHANGELOG.md';

    /**
     * Header description.
     *
     * @var string
     */
    public $headerTitle = 'Changelog';

    /**
     * Header title.
     *
     * @var string
     */
    public $headerDescription = 'All notable changes to this project will be documented in this file.';

    /**
     * Types allowed on changelog and labels (preserve the order).
     *
     * @var string[][]
     */
    public $types = [
        'feat' => ['label' => 'Features', 'description' => 'New features'],
        'perf' => ['label' => 'Performance Improvements', 'description' => 'Code changes that improves performance'],
        'fix' => ['label' => 'Bug Fixes', 'description' => 'Issues resolution'],
        'refactor' => ['label' => 'Code Refactoring', 'description' => 'A code change that neither fixes a bug nor adds a feature'],
        'style' => ['label' => 'Styles', 'description' => 'Changes that do not affect the meaning of the code'],
        'test' => ['label' => 'Tests', 'description' => 'Adding missing tests or correcting existing tests'],
        'build' => ['label' => 'Builds', 'description' => 'Changes that affect the build system or external dependencies '],
        'ci' => ['label' => 'Continuous Integrations', 'description' => 'Changes to CI configuration files and scripts'],
        'docs' => ['label' => 'Documentation', 'description' => 'Documentation changes'],
        'chore' => ['label' => 'Chores', 'description' => "Other changes that don't modify the source code or test files"],
        'revert' => ['label' => 'Reverts', 'description' => 'Reverts a previous commit'],
    ];

    /**
     * Ignore message commit patterns.
     *
     * @var string[]
     */
    public $ignorePatterns = [
        '/^chore\(release\):/i',
    ];

    /**
     * Constructor.
     */
    public function __construct(array $settings = [])
    {
        $this->fromArray($settings);
    }

    /**
     * From array.
     *
     * @param $array
     */
    public function fromArray(array $array)
    {
        if (empty($array)) {
            return;
        }

        $defaults = [
            'headerTitle' => $this->headerTitle,
            'headerDescription' => $this->headerDescription,
            'fileName' => $this->fileName,
            'types' => $this->types,
            'excludedTypes' => ['build', 'chore', 'ci', 'docs', 'refactor', 'revert', 'style', 'test'],
        ];

        $params = array_replace_recursive($defaults, $array);

        // Overwrite excluded types
        if (!empty($array['excludedTypes'])) {
            $params['excludedTypes'] = $array['excludedTypes'];
        }

        // Unset excluded types
        if (is_array($params['excludedTypes'])) {
            foreach ($params['excludedTypes'] as $type) {
                unset($params['types'][$type]);
            }
        }

        // Ignore patterns
        if (!empty($array['ignorePatterns'])) {
            $params['ignorePatterns'] = array_merge($this->ignorePatterns, $array['ignorePatterns']);
        }

        // Check ignore patterns
        foreach ($params['ignorePatterns'] as $key => $pattern) {
            if (!$this->isRegex($pattern)) {
                $params['ignorePatterns'][$key] = '#' . preg_quote($pattern, '#') . '#i';
            }
        }

        $this->setTypes($params['types']);
        $this->setHeaderTitle($params['headerTitle']);
        $this->setHeaderDescription($params['headerDescription']);
        $this->setFileName($params['fileName']);
        $this->setIgnorePatterns($params['ignorePatterns']);
    }

    /**
     * Check if is regex.
     *
     * @param $pattern
     *
     * @return bool
     */
    protected function isRegex(string $pattern)
    {
        return @preg_match($pattern, null) !== false;
    }

    /**
     * @return string[][]
     */
    public function getTypesInfo(): array
    {
        return $this->types;
    }

    /**
     * @return string[]
     */
    public function getTypes(): array
    {
        return array_keys($this->types);
    }

    /**
     * @param $type
     */
    public function getTypeLabel(string $type): string
    {
        return $this->types[$type]['label'];
    }

    /**
     * @param string[][] $types
     */
    public function setTypes(array $types): Configuration
    {
        $this->types = $types;

        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): Configuration
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getHeaderTitle(): string
    {
        return $this->headerTitle;
    }

    public function setHeaderTitle(string $headerTitle): Configuration
    {
        $this->headerTitle = $headerTitle;

        return $this;
    }

    public function getHeaderDescription(): string
    {
        return $this->headerDescription;
    }

    public function setHeaderDescription(string $headerDescription): Configuration
    {
        $this->headerDescription = $headerDescription;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getIgnorePatterns(): array
    {
        return $this->ignorePatterns;
    }

    /**
     * @param string[] $ignorePatterns
     */
    public function setIgnorePatterns(array $ignorePatterns): Configuration
    {
        $this->ignorePatterns = $ignorePatterns;

        return $this;
    }
}
