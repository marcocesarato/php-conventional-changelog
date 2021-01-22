<?php

namespace ConventionalChangelog\Helper;

use DateTime;

class Git
{
    /**
     * Run shell command on working dir.
     *
     * @param $string
     */
    protected static function shellExec($string): string
    {
        $value = shell_exec($string);

        return Format::clean((string)$value);
    }

    /**
     * Get first commit hash.
     */
    public static function getFirstCommit(): string
    {
        return self::shellExec('git rev-list --max-parents=0 HEAD');
    }

    /**
     * Get last tag.
     */
    public static function getLastTag(): string
    {
        return self::shellExec('git describe --tags --abbrev=0');
    }

    /**
     * Get commit date.
     */
    public static function getCommitDate($hash): string
    {
        $date = self::shellExec("git log -1 --format=%aI {$hash}");
        $today = new DateTime($date);

        return $today->format('Y-m-d');
    }

    /**
     * Get last tag commit hash.
     */
    public static function getLastTagCommit(): string
    {
        $lastTag = self::getLastTag();

        return self::shellExec("git rev-parse --verify {$lastTag}");
    }

    /**
     * Get remote url.
     */
    public static function getRemoteUrl(): string
    {
        $url = self::shellExec('git config --get remote.origin.url');
        $url = preg_replace("/\.git$/", '', $url);
        $url = preg_replace('/^(https?:\/\/)([0-9a-z.\-_:%]+@)/i', '$1', $url);

        return $url;
    }

    /**
     * Get commits.
     */
    public static function getCommits(string $options = ''): array
    {
        $commits = self::shellExec("git log --pretty=format:'%B%H----DELIMITER----' {$options}") . "\n";

        $commitsArray = explode("----DELIMITER----\n", $commits);
        array_pop($commitsArray);

        return $commitsArray;
    }

    /**
     * Get tags.
     */
    public static function getTags(): array
    {
        $tags = self::shellExec("git tag --sort=-creatordate --list --format='%(refname:strip=2)----DELIMITER----'") . "\n";
        $tagsArray = explode("----DELIMITER----\n", $tags);
        array_pop($tagsArray);

        $tagsArray = array_reverse($tagsArray);

        return $tagsArray;
    }

    /**
     * Commit.
     *
     * @return string
     */
    public static function commit(string $message, array $files = [], bool $amend = false, bool $verify = true)
    {
        foreach ($files as $file) {
            system("git add \"{$file}\"");
        }
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
}
