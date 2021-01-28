<?php

namespace ConventionalChangelog\Git;

use ConventionalChangelog\Helper\Formatter;
use DateTime;

class Repository
{
    /**
     * @var string
     */
    protected static $delimiter = '----DELIMITER---';

    /**
     * Run shell command on working dir.
     *
     * @param $string
     */
    protected static function run($string): string
    {
        $value = shell_exec($string);
        $value = Formatter::clean((string)$value);

        // Fix for some git versions
        $value = trim($value, "'");
        $value = str_replace(self::$delimiter . "'\n'", self::$delimiter . "\n", $value);

        return $value;
    }

    /**
     * Is inside work tree.
     */
    public static function isInsideWorkTree(): bool
    {
        $result = self::run('git rev-parse --is-inside-work-tree');

        return $result === 'true';
    }

    /**
     * Get first commit hash.
     */
    public static function getFirstCommit(): string
    {
        return self::run('git rev-list --max-parents=0 HEAD');
    }

    /**
     * Get last tag.
     */
    public static function getLastTag(): string
    {
        return self::run("git for-each-ref refs/tags --sort=-creatordate --format='%(refname:strip=2)' --count=1");
    }

    /**
     * Get commit date.
     */
    public static function getCommitDate($hash): string
    {
        $date = self::run("git log -1 --format=%aI {$hash}");
        $today = new DateTime($date);

        return $today->format('Y-m-d');
    }

    /**
     * Get last tag commit hash.
     */
    public static function getLastTagCommit(): string
    {
        $lastTag = self::getLastTag();

        return self::run("git rev-parse --verify {$lastTag}");
    }

    /**
     * Get remote url.
     */
    public static function getRemoteUrl(): string
    {
        $url = self::run('git config --get remote.origin.url');
        $url = preg_replace("/\.git$/", '', $url);
        $url = preg_replace('/^(https?:\/\/)([0-9a-z.\-_:%]+@)/i', '$1', $url);

        return $url;
    }

    /**
     * Get commits.
     */
    public static function getCommits(string $options = ''): array
    {
        $commits = [];
        $shortcodes = [
            'raw' => '%B',
            'hash' => '%H',
            'authorName' => '%aN',
            'authorEmail' => '%aE',
            'authorDate' => '%aI',
            'committerName' => '%cN',
            'committerEmail' => '%cE',
            'committerDate' => '%cI',
        ];

        $format = '';
        foreach ($shortcodes as $key => $value) {
            $format .= "[{$key}]{$value}[/{$key}]";
        }
        $format .= self::$delimiter;
        $commitsLogs = self::run("git log --pretty=format:'" . $format . "' {$options}") . "\n";

        $commitsArray = explode(self::$delimiter . "\n", $commitsLogs);
        array_pop($commitsArray);

        $shortcodesKeys = array_keys($shortcodes);
        foreach ($commitsArray as $commitRaw) {
            $parse = self::parseShortcodes($commitRaw, $shortcodesKeys);
            $commit = new Commit();
            $commit->fromArray($parse);
            $commits[] = $commit;
        }

        return $commits;
    }

    /**
     * Get tags.
     */
    public static function getTags(): array
    {
        $tags = self::run("git tag --sort=-creatordate --list --format='%(refname:strip=2)" . self::$delimiter . "'") . "\n";
        $tagsArray = explode(self::$delimiter . "\n", $tags);
        array_pop($tagsArray);

        $tagsArray = array_reverse($tagsArray);

        return $tagsArray;
    }

    /**
     * Add all.
     *
     * @return string
     */
    public static function addAll()
    {
        system('git add -all');
    }

    /**
     * Add files.
     *
     * @return string
     */
    public static function add($files)
    {
        if (!is_array($files)) {
            $files = [$files];
        }
        foreach ($files as $file) {
            system("git add \"{$file}\"");
        }
    }

    /**
     * Commit.
     *
     * @return string
     */
    public static function commit(string $message, array $files = [], bool $amend = false, bool $verify = true)
    {
        self::add($files);
        $message = str_replace('"', "'", $message); // Escape
        $command = "git commit -m \"{$message}\"";
        if ($amend) {
            $command .= ' --amend';
        }
        if (!$verify) {
            $command .= ' --no-verify';
        }

        return exec($command);
    }

    /**
     * Tag.
     *
     * @return string
     */
    public static function tag(string $name)
    {
        return exec("git tag {$name}");
    }

    /**
     * Parse shortcode.
     *
     * @param $content
     * @param $shortcodes
     *
     * @return array
     */
    protected static function parseShortcodes($content, $shortcodes)
    {
        $result = [];
        foreach ($shortcodes as $key) {
            $result[$key] = null;
            $key = preg_quote($key, '/');
            $pattern = "/\[[\s]*" . $key . "[\s]*\](.+?)\[[\s]*\/[\s]*" . $key . "[\s]*\]/si";
            preg_match_all($pattern, $content, $match);
            if (count($match) > 0 && !empty($match[0]) && isset($match[1][0])) {
                $result[$key] = $match[1][0];
            }
        }

        return $result;
    }
}
