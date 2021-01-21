<?php

namespace ConventionalChangelog;

class Configuration
{
    /**
     * Changelog filename.
     *
     * @var string
     */
    public $path = 'CHANGELOG.md';

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
     * Preset of types allowed on changelog and labels.
     * Sorting must be preserved.
     *
     * @var string[][]
     */
    public $preset = [
        'feat' => ['label' => 'Features', 'description' => 'New features'],
        'perf' => ['label' => 'Performance Improvements', 'description' => 'Code changes that improves performance'],
        'fix' => ['label' => 'Bug Fixes', 'description' => 'Bugs and issues resolution'],
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
     * Preset of breaking changes.
     *
     * @var string[][]
     */
    public $breakingPreset = [
        'breaking_changes' => ['label' => '⚠ BREAKING CHANGES', 'description' => 'Code changes that potentially causes other components to fail'],
    ];

    /**
     * Types allowed on changelog.
     *
     * @var string[][]
     */
    public $types = [];

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
            'headerTitle' => $this->getHeaderTitle(),
            'headerDescription' => $this->getHeaderDescription(),
            'path' => $this->getPath(),
            'preset' => $this->getPreset(),
            'types' => [],
            'ignoreTypes' => ['build', 'chore', 'ci', 'docs', 'refactor', 'revert', 'style', 'test'],
        ];

        $params = array_replace_recursive($defaults, $array);

        // Ignore Types
        if (!empty($array['ignoreTypes'])) {
            $params['ignoreTypes'] = $array['ignoreTypes']; // Overwrite ignored types
        }
        if (is_array($params['ignoreTypes'])) {
            foreach ($params['ignoreTypes'] as $type) {
                unset($params['preset'][$type]);  // Unset excluded types
            }
        }

        // Ignore Patterns
        if (!empty($array['ignorePatterns'])) {
            $params['ignorePatterns'] = array_merge($this->ignorePatterns, $array['ignorePatterns']);
        }
        foreach ($params['ignorePatterns'] as $key => $pattern) {
            if (!$this->isRegex($pattern)) { // Check ignore patterns
                $params['ignorePatterns'][$key] = '#' . preg_quote($pattern, '#') . '#i';
            }
        }

        // Set Types (overwrite ignored types)
        if (!empty($array['types'])) {
            foreach ($this->preset as $type => $value) {
                if (!in_array($type, $array['types'])) {
                    if (isset($params['preset'][$type])) {
                        unset($params['preset'][$type]);
                    }
                } else {
                    $params['preset'][$type] = $value;
                }
            }
        }

        // Add breaking changes
        $params['preset'] = array_merge($this->breakingPreset, $params['preset']);

        $this->setTypes($params['preset']);
        $this->setHeaderTitle($params['headerTitle']);
        $this->setHeaderDescription($params['headerDescription']);
        $this->setPath($params['path']);
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
     * @return string[]
     */
    public function getTypes(): array
    {
        return array_keys($this->types);
    }

    /**
     * @return string[]
     */
    public function getAllowedTypes(): array
    {
        $types = $this->types;
        unset($types['breaking_changes']);

        return array_keys($types);
    }

    /**
     * @param $type
     */
    public function getTypeLabel(string $type): string
    {
        return $this->types[$type]['label'];
    }

    /**
     * @param $type
     */
    public function getTypeDescription(string $type): string
    {
        return isset($this->types[$type]['description']) ? $this->types[$type]['description'] : '';
    }

    /**
     * @param string[][] $types
     */
    public function setTypes(array $types): Configuration
    {
        $this->types = $types;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): Configuration
    {
        $this->path = $path;

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

	/**
	 * @return string[][]
	 */
	public function getPreset() : array
	{
		return array_merge($this->breakingPreset, $this->preset);
	}
}
