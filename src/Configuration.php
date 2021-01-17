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
        'feat' => ['label' => 'Features'],
        'perf' => ['label' => 'Performance Features'],
        'fix' => ['label' => 'Fixes'],
        'refactor' => ['label' => 'Refactoring'],
        'docs' => ['label' => 'Docs'],
        'chore' => ['label' => 'Chores'],
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
            'types' => $this->types,
            'excludedTypes' => [],
            'headerTitle' => $this->headerTitle,
            'headerDescription' => $this->headerDescription,
            'fileName' => $this->fileName,
            'ignorePatterns' => $this->ignorePatterns,
        ];

        $params = array_replace_recursive($defaults, $array);

        if (is_array($params['excludedTypes'])) {
            foreach ($params['excludedTypes'] as $type) {
                unset($params['types'][$type]);
            }
        }

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
    public function getTypes(): array
    {
        return $this->types;
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
