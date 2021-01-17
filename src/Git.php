<?php

namespace ConventionalChangelog;

use DateTime;

class Git
{
    /**
     * Run shell command on working dir.
     *
     * @param $string
     *
     * @return string
     */
    protected static function shellExec($string)
    {
        $value = shell_exec($string);

        return Utils::clean($value);
    }

    /**
     * Get first commit hash.
     *
     * @return string
     */
    public static function getFirstCommit()
    {
        return self::shellExec('git rev-list --max-parents=0 HEAD');
    }

    /**
     * Get last tag.
     *
     * @return string
     */
    public static function getLastTag()
    {
        return self::shellExec('git describe --tags --abbrev=0');
    }

    /**
     * Get commit date.
     *
     * @return string
     */
    public static function getCommitDate($hash)
    {
        $date = self::shellExec("git log -1 --format=%aI {$hash}");
        $today = new DateTime($date);

        return $today->format('Y-m-d');
    }

    /**
     * Get last tag commit hash.
     *
     * @return string
     */
    public static function getLastTagCommit()
    {
        $lastTag = self::getLastTag();

        return self::shellExec("git rev-parse --verify {$lastTag}");
    }

    /**
     * Get remote url.
     *
     * @return string
     */
    public static function getRemoteUrl()
    {
        $url = self::shellExec('git config --get remote.origin.url');
        $url = preg_replace("/\.git$/", '', $url);
        $url = preg_replace('/^(https?:\/\/)([0-9a-z.\-_:%]+@)/i', '$1', $url);

        return $url;
    }

    /**
     * Get commits.
     *
     * @param $options
     *
     * @return array
     */
    public static function getCommits($options = '')
    {
        $commits = self::shellExec("git log --format=%B%H----DELIMITER---- {$options}") . "\n";
        $commitsArray = explode("----DELIMITER----\n", $commits);
        array_pop($commitsArray);

        return $commitsArray;
    }

    /**
     * Get tags.
     *
     * @return array
     */
    public static function getTags()
    {
        $tags = self::shellExec('git tag --list --format=%(refname:strip=2)----DELIMITER----') . "\n";
        $tagsArray = explode("----DELIMITER----\n", $tags);
        array_pop($tagsArray);

        return $tagsArray;
    }

    /**
     * Commit.
     *
     * @param $message
     * @param $files
     */
    public static function commit($message, $files = [])
    {
        $message = str_replace('"', "'", $message); // Escape
        $command = "git commit -m \"chore(release): {$message}\"";
        if (empty($files)) {
            $command .= ' -- ' . implode(' ', $files);
        }
        system($command);
    }

    /**
     * Tag.
     *
     * @param $name
     */
    public static function tag($name)
    {
        system("git tag {$name}");
    }
}
