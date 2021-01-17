<?php

namespace ConventionalChangelog;

use DateTime;

class Generator
{
    /**
     * Changelog filename.
     * @var string 
     */
    public static $fileName = 'CHANGELOG.md';

    /**
     * Types allowed on changelog and labels (preserve the order).
     * @var string[][] 
     */
    public static $types = [
        'feat' => ['code' => 'feat', 'label' => 'Features'],
        'fix' => ['code' => 'fix', 'label' => 'Fixes'],
        'perf' => ['code' => 'perf', 'label' => 'Performance Features'],
        'refactor' => ['code' => 'refactor', 'label' => 'Refactoring'],
        'docs' => ['code' => 'docs', 'label' => 'Docs'],
        'chore' => ['code' => 'chore', 'label' => 'Chores'],
    ];

    /**
     * Changelog pattern.
     * @var string
     */
    public static $header = "# Changelog\nAll notable changes to this project will be documented in this file.\n\n\n";

    /**
     * Ignore message commit patterns.
     * @var string[]
     */
    public static $ignorePatterns = [
        '/^chore\(release\):/i'
    ];

    /**
     * Run generation.
     */
    public function run()
    {
        $root = getcwd(); // Root

        // Arguments
        $helper = <<<EOL
-c      --commit        bool        Commit the new release once changelog is generated
-f      --from-date     str         Get commits from specified date [YYYY-MM-DD]
-h      --help          bool        Show the helper with all commands available
-m      --major         bool        Major release (important changes)
-n      --minor         bool        Minor release (add functionality)
-p      --patch         bool        Patch release (bug fixes) [default]
-t      --to-date       str         Get commits last tag date (or specified on --from-date) to specified date [YYYY-MM-DD]
-v      --version       str         Specify next release version code (Semver)
EOL;

        $this->arg($helper);

        // Help command
        $help = $this->arg('help', false);
        if ($help) {
            exit("\n======= CHANGELOG HELPER =======\n\n{$helper}\n");
        }

        $autoCommit = $this->arg('commit', false); // Commit once changelog is generated
        $fromDate = $this->arg('from-date', null);
        $toDate = $this->arg('to-date', null);

        $patchRelease = $this->arg('patch', false);
        $minorRelease = $this->arg('minor', false);
        $majorRelease = $this->arg('major', false);

        // Current Dates
        $today = new DateTime();
        $todayString = $this->getDateString($today);

        // Last version
        $lastVersion = shell_exec('git describe --tags --abbrev=0');
        $lastVersion = $this->clean($lastVersion);

        // Last version commit
        $lastVersionCommit = shell_exec("git rev-parse --verify {$lastVersion}");
        $lastVersionCommit = $this->clean($lastVersionCommit);

        // Last version date
        $lastVersionDate = shell_exec("git log -1 --format=%ai {$lastVersion}");
        $lastVersionDate = $this->clean($lastVersionDate);

        // Generate new version code
        $newVersion = $this->increaseSemVer($lastVersion, $majorRelease, $minorRelease, $patchRelease);
        $newVersion =$this-> arg('version', $newVersion);
        $newVersion = preg_replace('#^v#i', '', $newVersion);

        // Remote url
        $url = shell_exec('git config --get remote.origin.url');
        $url = $this->clean($url);
        $url = preg_replace("/\.git$/", '', $url);
        $url = preg_replace('/^(https?:\/\/)([0-9a-z.\-_:%]+@)/i', '$1', $url);

        // Get latest commits from last version date to current date
        $additionalParams = "{$lastVersion}..HEAD";
        if (!empty($fromDate) ||
            !empty($toDate)) {
            $additionalParams = '';
            if (!empty($fromDate)) {
                $additionalParams .= ' --since="' . date('Y-m-d', strtotime($fromDate)) . '"';
            }
            if (!empty($toDate)) {
                $time = strtotime($toDate);
                $additionalParams .= ' --before="' . date('Y-m-d', $time) . '"';
                $today->setTimestamp($time);
                $todayString = $this->getDateString($today);
            }
        }

        $gitLog = shell_exec("git log --format=%B%H----DELIMITER---- {$additionalParams}");
        $commitsRaw = explode("----DELIMITER----\n", $gitLog);

        // Get all commits information
        $commits = [];
        foreach ($commitsRaw as $commit) {
            $rows = explode("\n", $commit);
            $count = count($rows);
            // Commit info
            $head = $this->clean($rows[0]);
            $sha = $this->clean($rows[$count - 1]);
            $message = '';
            // Get message
            for ($i = 0; $i < $count; $i++) {
                $row = $rows[$i];
                if ($i !== 0 && $i !== $count - 1) {
                    $message .= $row . "\n";
                }
            }
            // Check ignored commit
            $ignore = false;
            foreach (self::$ignorePatterns as $pattern) {
                if (preg_match($pattern, $head)) {
                    $ignore = true;
                    break;
                }
            }
            // Add commit
            if (!empty($sha) && !$ignore) {
                $commits[] = [
                    'sha' => $sha,
                    'head' => $head,
                    'message' => $this->clean($message),
                ];
            }
        }

        // Changes groups
        $changes = [];
        foreach (self::$types as $key => $type) {
            $changes[$key] = [];
        }

        // Group all changes to lists by type
        foreach ($commits as $commit) {
            foreach (self::$types as $key => $type) {
                $head = $this->clean($commit['head']);
                $code = preg_quote($type['code'], '/');
                if (preg_match('/^' . $code . '(\(.*?\))?[:]?\\s/i', $head)) {
                    $parse = $this->parseCommitHead($head, $type['code']);
                    $context = $parse['context'];
                    $description = $parse['description'];
                    $sha = $commit['sha'];
                    $short = substr($sha, 0, 6);
                    // List item
                    $itemKey = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '', $description));
                    $changes[$key][$context][$itemKey][$sha] = [
                        'description' => $description,
                        'short' => $short,
                        'url' => $url,
                        'sha' => $sha,
                    ];
                }
            }
        }

        // File
        $file = $root.DIRECTORY_SEPARATOR.self::$fileName;

        // Initialize changelogs
        $changelogCurrent = '';
        $changelogNew = "## [{$newVersion}]($url/compare/{$lastVersion}...v{$newVersion}) ({$today->format('Y-m-d')})\n\n";

        // Get changelogs content
        if (file_exists($file)) {
            $header = ltrim(self::$header);
            $header = preg_quote($header, '/');
            $changelogCurrent = file_get_contents($file);
            $changelogCurrent = ltrim($changelogCurrent);
            $changelogCurrent = preg_replace("/^$header/i", '', $changelogCurrent);
        }

        // Add all changes list to new changelog
        $changelogNew .= $this->getMarkdownChanges($changes);

        // Save new changelog prepending the current one
        file_put_contents($file, self::$header."{$changelogNew}{$changelogCurrent}");

        // Create commit and add tag
        if ($autoCommit) {
            system("git commit -m \"chore(release): {$newVersion}\"");
            system("git tag v{$newVersion}");
        }
    }

    /**
     * Generate markdown from changes.
     *
     * @param $changes
     *
     * @return string
     */
    protected function getMarkdownChanges($changes)
    {
        $changelog = '';
        // Add all changes list to new changelog
        foreach ($changes as $type => $list) {
            if (empty($list)) {
                continue;
            }
            ksort($list);
            $changelog .= PHP_EOL . "### ".self::$types[$type]['label'] . "\n\n";
            foreach ($list as $context => $items) {
                asort($items);
                if (is_string($context) && !empty($context)) {
                    // Context section
                    $changelog .= PHP_EOL . "##### {$context}" . "\n\n";
                }
                foreach ($items as $itemsList) {
                    $description = '';
                    $sha = '';
                    $shaGroup = [];
                    foreach ($itemsList as $item) {
                        $description = $item['description'];
                        if (!empty($item['sha'])) {
                            $shaGroup[] = "[{$item['short']}]({$item['url']}/commit/{$item['sha']})";
                        }
                    }
                    if (!empty($shaGroup)) {
                        $sha = '(' . implode(', ', $shaGroup) . ')';
                    }
                    $changelog .= "* {$description} {$sha}\n";
                }
            }
        }
        // Add version separator
        $changelog .= PHP_EOL . '---' . "\n\n";

        return $changelog;
    }

    /**
     * Parse conventional commit head.
     *
     * @param string $message
     * @param string $type
     *
     * @return array
     */
    protected function parseCommitHead($head, $type)
    {
        $parse = [
            'context' => null,
            'description' => $this->clean($head),
        ];

        $descriptionType = preg_quote(substr($parse['description'], 0, strlen($type)), '/');
        $parse['description'] = preg_replace('/^' . $descriptionType . '[:]?\s*/i', '', $parse['description']);
        $parse['description'] = preg_replace('/^\((.*?)\)[!]?[:]?\s*/', '**$1**: ', $this->clean($parse['description']));
        $parse['description'] = preg_replace('/\s+/m', ' ', $parse['description']);

        // Set context
        if (preg_match("/^\*\*(.*?)\*\*:(.*?)$/", $parse['description'], $match)) {
            $parse['context'] = $this->clean($match[1]);
            $parse['description'] = $this->clean($match[2]);

            // Unify context labels
            $parse['context'] = preg_replace('/[_]+/m', ' ', $parse['context']);
            $parse['context'] = ucfirst($parse['context']);
            $parse['context'] = preg_replace('/((?<=\p{Ll})\p{Lu})|((?!\A)\p{Lu}(?>\p{Ll}))/u', ' $0', $parse['context']);
            $parse['context'] = preg_replace('/\.(php|md|json|txt|csv)($|\s)/', '', $parse['context']);
            $parse['context'] = $this->clean($parse['context']);
        }

        $parse['description'] = ucfirst($parse['description']);

        return $parse;
    }

    /**
     * Clean string removing double spaces and trim.
     *
     * @param $string
     *
     * @return string
     */
    protected function clean($string)
    {
        $string = trim($string);

        return preg_replace('/\s+/m', ' ', $string);
    }

    /**
     *  Get today date string formatted.
     *
     * @param DateTime $today
     *
     * @return string
     */
    protected function getDateString($date)
    {
        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'Semptember',
            'October',
            'Novembrer',
            'December',
        ];
        $day = date('j', $date->getTimestamp());
        $month = date('n', $date->getTimestamp());
        $monthName = $months[$month - 1];
        $year = date('Y', $date->getTimestamp());

        return $day . ' ' . $monthName . ' ' . $year;
    }

    /**
     * Increase SemVer.
     *
     * @param $version
     * @param false $major
     * @param false $minor
     * @param bool $patch
     *
     * @return string
     */
    protected function increaseSemVer($version, $major = false, $minor = false, $patch = false)
    {
        $newVersion = [0, 0, 0];
        $increaseKeys = [];

        $version = preg_replace('#^v#i', '', $version);

        // Generate new version code
        $parts = explode('.', $version);

        foreach ($parts as $key => $value) {
            $newVersion[$key] = (int)$value;
        }

        // Increase major
        if ($major) {
            $increaseKeys[] = 0;
        }

        // Increase minor
        if ($minor) {
            $increaseKeys[] = 1;
        }

        // Increase patch
        if ($patch || (!$major && !$minor && !$patch)) {
            $increaseKeys[] = 2;
        }

        foreach ($increaseKeys as $key) {
            $newVersion[$key]++;
        }

        // Recompose semver
        return implode('.', $newVersion);
    }

    /**
     * Argument.
     *
     * @param string $x
     * @param null $default
     *
     * @return array|mixed|null
     */
    protected function arg($x = '', $default = null)
    {
        static $arginfo = [];

        /* helper */
        $contains = function ($h, $n) {
            return false !== strpos($h, $n);
        };
        /* helper */
        $valuesOf = function ($s) {
            return explode(',', $s);
        };

        //  called with a multiline string --> parse arguments
        if ($contains($x, "\n")) {
            //  parse multiline text input
            $args = $GLOBALS['argv'] ?: [];
            $rows = preg_split('/\s*\n\s*/', trim($x));
            $data = $valuesOf('char,word,type,help');
            foreach ($rows as $row) {
                list($char, $word, $type, $help) = preg_split('/\s\s+/', $row);
                $char = trim($char, '-');
                $word = trim($word, '-');
                $key = $word ?: $char ?: '';
                if ($key === '') {
                    continue;
                }
                $arginfo[$key] = compact($data);
                $arginfo[$key]['value'] = null;
            }

            $nr = 0;
            while ($args) {
                $x = array_shift($args);
                if ($x[0] != '-') {
                    $arginfo[$nr++]['value'] = $x;
                    continue;
                }
                $x = ltrim($x, '-');
                $v = null;
                if ($contains($x, '=')) {
                    list($x, $v) = explode('=', $x, 2);
                }
                $k = '';
                foreach ($arginfo as $k => $arg) {
                    if (($arg['char'] == $x) || ($arg['word'] == $x)) {
                        break;
                    }
                }
                $t = $arginfo[$k]['type'];
                switch ($t) {
                    case 'bool':
                        $v = true;
                        break;
                    case 'str':
                        if (is_null($v)) {
                            $v = array_shift($args);
                        }
                        break;
                    case 'int':
                        if (is_null($v)) {
                            $v = array_shift($args);
                        }
                        $v = (int)$v;
                        break;
                }
                $arginfo[$k]['value'] = $v;
            }

            return $arginfo;
        }

        //  called with a question --> read argument value
        if ($x === '') {
            return $arginfo;
        }
        if (isset($arginfo[$x]['value'])) {
            return $arginfo[$x]['value'];
        }

        return $default;
    }
}