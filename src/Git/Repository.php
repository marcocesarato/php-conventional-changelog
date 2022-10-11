<?php

namespace ConventionalChangelog\Git;

use ConventionalChangelog\Helper\ShellCommand;
use DateTime;

class Repository
{
    /**
     * @var string
     */
    protected static $delimiter = '----DELIMITER---';

    /**
     * Run shell command on working dir.
     */
    protected static function run(string $string): string
    {
        $value = ShellCommand::exec($string);

        // Fix for some git versions
        $value = trim($value, "'");

        return str_replace(self::$delimiter . "'\n'", self::$delimiter . "\n", $value);
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
     * Get last commit hash.
     */
    public static function getLastCommit(): string
    {
        return self::run('git log -1 --pretty=format:%H');
    }

    /**
     * Get last tag.
     */
    public static function getLastTag(): string
    {
        return self::run('git describe --tags --exclude "*-*" --abbrev=0');
    }

    /**
     * Get last alpha tag based on a tag pattern.
     */
    public static function getLastAlphaTag(string $lastTag = ''): ?string
    {
        return self::getTag($lastTag, 'alpha');
    }

    /**
     * Get last release candidate tag based on a tag pattern.
     */
    public static function getLastReleaseCandidateTag(string $lastTag = ''): ?string
    {
        return self::getTag($lastTag, 'rc');
    }

    /**
     * Get last beta tag based on a tag pattern.
     */
    public static function getLastBetaTag(string $lastTag = ''): ?string
    {
        return self::getTag($lastTag, 'beta');
    }

    private static function getTag(string $tag, string $match): ?string
    {
        $find = self::run(sprintf('git describe --tags --match "*%s*-%s*" --abbrev=0', $tag, $match));

        return !empty($find) ? $find : null;
    }

    /**
     * Get last tag commit hash.
     */
    public static function getLastTagCommit($prefix = ''): string
    {
        $lastTag = self::getLastTag($prefix);

        return self::run("git rev-parse --verify {$lastTag}");
    }

    /**
     * Get current branch name.
     */
    public static function getCurrentBranch(): string
    {
        return self::run('git branch --show-current');
    }

    /**
     * Get commit date.
     */
    public static function getCommitDate($hash): DateTime
    {
        $date = self::run("git log -1 --format=%aI {$hash}");

        return new DateTime($date);
    }

    /**
     * Get remote url.
     */
    public static function getRemoteUrl(): string
    {
        $url = self::run('git config --get remote.origin.url');
        $url = preg_replace("/\.git$/", '', $url);

        return preg_replace('/^(https?:\/\/)([0-9a-z.\-_:%]+@)/i', '$1', $url);
    }

    /**
     * Has remote url.
     */
    public static function hasRemoteUrl(): bool
    {
        $url = self::getRemoteUrl();

        return !empty($url);
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
    public static function getTags($prefix = ''): array
    {
        $tags = self::run("git tag '" . $prefix . "*' --sort=-v:refname --list --format='%(refname:strip=2)" . self::$delimiter . "'") . "\n";
        $tagsArray = explode(self::$delimiter . "\n", $tags);
        array_pop($tagsArray);

        return array_reverse($tagsArray);
    }

    /**
     * Add all.
     *
     * @return string
     */
    public static function addAll()
    {
        system('git add --all');
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
    public static function commit(string $message, array $files = [], bool $amend = false, bool $verify = true, $noEdit = false)
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
        if ($noEdit) {
            $command .= ' --no-edit';
        }

        return exec($command);
    }

    /**
     * Tag.
     *
     * @return string
     */
    public static function tag(string $name, $annotation = false)
    {
        $message = $annotation ?: $name;
        $flags = $annotation !== false ? "-a -m {$message}" : '';

        return exec("git tag {$flags} {$name}");
    }

    /**
     * Delete Tag.
     *
     * @return string
     */
    public static function deleteTag(string $name)
    {
        return exec("git tag -d {$name}");
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

    /**
     * Parse remote url.
     *
     * @return array
     */
    public static function parseRemoteUrl()
    {
        $url = self::getRemoteUrl();
        $patterns = [
            '#^(?P<protocol>https?|git|ssh|rsync)\://(?:(?P<user>.+)@)*(?P<host>[a-z0-9_.-]*)[:/]*(?P<port>[\d]+){0,1}(?P<pathname>\/((?P<owner>.+)\/)?((?P<repository>.+?)(\.git|\/)?)?)$#smi',
            '#(git\+)?((?P<protocol>\w+)://)((?P<user>\w+)@)?((?P<host>[\w\.\-]+))(:(?P<port>\d+))?(?P<pathname>(\/(?P<owner>.+)/)?(\/?(?P<repository>.+)(\.git|\/)?)?)$#smi',
            '#^(?:(?P<user>.+)@)*(?P<host>[a-z0-9_.-]*)[:]*(?P<port>[\d]+){0,1}(?P<pathname>\/?(?P<owner>.+)/(?P<repository>.+).git)$#smi',
            '#((?P<user>\w+)@)?((?P<host>[\w\.\-]+))[\:\/]{1,2}(?P<pathname>((?P<owner>.+)/)?((?P<repository>.+)(\.git|\/)?)?)$#smi',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $match)) {
                return array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);
            }
        }

        return [];
    }
}
