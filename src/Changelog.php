<?php

namespace ConventionalChangelog;

use DateTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Changelog
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * Changelog constructor.
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Generate changelog.
     */
    public function generate(InputInterface $input, SymfonyStyle $output): int
    {
        $root = $input->getArgument('path'); // Root

        $autoCommit = $input->getOption('commit'); // Commit once changelog is generated
        $autoTag = !$input->getOption('no-tag'); // Tag release once is committed
        $amend = $input->getOption('amend'); // Amend commit
        $hooks = !$input->getOption('no-verify'); // Verify git hooks
        $fromDate = $input->getOption('from-date');
        $toDate = $input->getOption('to-date');
        $history = $input->getOption('history');

        $firstRelease = $input->getOption('first-release');
        $alphaRelease = $input->getOption('alpha');
        $betaRelease = $input->getOption('beta');
        $preRelease = $input->getOption('rc');
        $patchRelease = $input->getOption('patch');
        $minorRelease = $input->getOption('minor');
        $majorRelease = $input->getOption('major');

        $autoBump = false;

        // If have amend option enable commit
        if ($amend) {
            $autoCommit = true;
        }

        // Initialize changelogs
        $file = $root . DIRECTORY_SEPARATOR . $this->config->getFileName();
        $changelogCurrent = '';
        $changelogNew = '';

        $mainHeaderPrefix = "<!--- BEGIN HEADER -->\n# ";
        $mainHeaderSuffix = "\n<!--- END HEADER -->\n\n";
        $mainHeaderContent = $this->config->getHeaderTitle() . "\n\n" . $this->config->getHeaderDescription();
        $mainHeader = $mainHeaderPrefix . $mainHeaderContent . $mainHeaderSuffix;

        // Get changelogs content
        if (file_exists($file)) {
            $changelogCurrent = file_get_contents($file);
            $changelogCurrent = ltrim($changelogCurrent);

            // Remove header
            $beginHeader = preg_quote($mainHeaderPrefix, '/');
            $endHeader = preg_quote($mainHeaderSuffix, '/');

            $pattern = '/^(' . $beginHeader . '(.*)' . $endHeader . ')/si';
            $pattern = preg_replace(['/[\n]+/', '/[\s]+/'], ['[\n]+', '[\s]+'], $pattern);

            $changelogCurrent = preg_replace($pattern, '', $changelogCurrent);
        }

        // Current Dates
        $today = new DateTime();
        $todayString = Utils::getDateString($today);

        // First commit
        $firstCommit = Git::getFirstCommit();

        if (!$firstRelease) {
            $lastVersion = Git::getLastTag(); // Last version
            $lastVersionCommit = Git::getLastTagCommit(); // Last version commit
            $lastVersionDate = Git::getCommitDate($lastVersionCommit); // Last version date

            $bumpMode = SemanticVersion::RELEASE_PATCH;

            if ($majorRelease) {
                $bumpMode = SemanticVersion::RELEASE_MAJOR;
            } elseif ($minorRelease) {
                $bumpMode = SemanticVersion::RELEASE_MINOR;
            } elseif ($patchRelease) {
                $bumpMode = SemanticVersion::RELEASE_PATCH;
            } elseif ($preRelease) {
                $bumpMode = SemanticVersion::RELEASE_RC;
            } elseif ($betaRelease) {
                $bumpMode = SemanticVersion::RELEASE_BETA;
            } elseif ($alphaRelease) {
                $bumpMode = SemanticVersion::RELEASE_ALPHA;
            } else {
                $autoBump = true;
            }

            // Generate new version code
            $semver = new SemanticVersion('1.4.0-rc.1');
            $newVersion = $semver->bump($bumpMode);
        }

        $nextVersion = $input->getOption('ver');
        if (!empty($nextVersion)) {
            $newVersion = $nextVersion;
        }
        $newVersion = preg_replace('#^v#i', '', $newVersion);

        if (empty($newVersion)) {
            $newVersion = '1.0.0';
        }

        // Remote url
        $url = Git::getRemoteUrl();

        $options = []; // Git retrieve options per version

        if ($history) {
            $changelogCurrent = ''; // Clean changelog file
            $tags = Git::getTags();

            $previousTag = null;
            foreach ($tags as $key => $toTag) {
                $fromTag = $firstCommit;
                if (!empty($previousTag) && $key !== 0) {
                    $fromTag = $previousTag;
                }
                $options[$toTag] = [
                    'from' => $fromTag,
                    'to' => $toTag,
                    'date' => Git::getCommitDate($toTag),
                    'options' => "{$fromTag}..{$toTag}",
                ];
                $previousTag = $toTag;
            }
            if ($autoCommit) {
                $options[$lastVersion] = [
                    'from' => $lastVersion,
                    'to' => $newVersion,
                    'date' => $today->format('Y-m-d'),
                    'options' => "{$lastVersion}..HEAD",
                ];
            }
            $options = array_reverse($options);
        } else {
            if ($firstRelease) {
                // Get all commits from the first one
                $additionalParams = "{$firstCommit}..HEAD";
                $lastVersion = $firstCommit;
            } else {
                // Get latest commits from last version date
                $additionalParams = "{$lastVersion}..HEAD";
            }

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
                    $todayString = Utils::getDateString($today);
                }
            }
            $options[$lastVersion] = [
                'from' => $lastVersion,
                'to' => $newVersion,
                'date' => $today->format('Y-m-d'),
                'options' => $additionalParams,
            ];
        }

        foreach ($options as $version => $params) {
            $commitsRaw = Git::getCommits($params['options']);

            // Get all commits information
            $commits = [];
            foreach ($commitsRaw as $commit) {
                $rows = explode("\n", $commit);
                $count = count($rows);
                // Commit info
                $head = Utils::clean($rows[0]);
                $sha = Utils::clean($rows[$count - 1]);
                $message = '';
                // Get message
                foreach ($rows as $i => $row) {
                    if ($i !== 0 && $i !== $count - 1) {
                        $message .= $row . "\n";
                    }
                }
                // Check ignored commit
                $ignore = false;
                foreach ($this->config->getIgnorePatterns() as $pattern) {
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
                        'message' => Utils::clean($message),
                    ];
                }
            }

            // Changes groups
            $changes = [];
            foreach ($this->config->getTypes() as $key => $type) {
                $changes[$key] = [];
            }

            // Group all changes to lists by type
            foreach ($commits as $commit) {
                foreach ($this->config->getTypes() as $name => $type) {
                    $head = Utils::clean($commit['head']);
                    $code = preg_quote($name, '/');
                    if (preg_match('/^' . $code . '(\(.*?\))?[:]?\\s/i', $head)) {
                        $parse = $this->parseCommitHead($head, $name);
                        $scope = $parse['scope'];
                        $description = $parse['description'];
                        $sha = $commit['sha'];
                        $short = substr($sha, 0, 6);
                        // List item
                        $itemKey = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '', $description));
                        $changes[$name][$scope][$itemKey][$sha] = [
                            'description' => $description,
                            'short' => $short,
                            'url' => $url,
                            'sha' => $sha,
                        ];
                    }
                }
            }

            // Initialize changelogs
            $changelogNew .= "## [{$params['to']}]($url/compare/{$params['from']}...v{$params['to']}) ({$params['date']})\n\n";
            // Add all changes list to new changelog
            $changelogNew .= $this->getMarkdownChanges($changes);
        }

        // Save new changelog prepending the current one
        file_put_contents($file, $mainHeader . "{$changelogNew}{$changelogCurrent}");
        $output->success('Changelog generated!');
        $output->writeln(" > Changelog file: {$file}");

        // Create commit
        if ($autoCommit) {
            $result = Git::commit("chore(release): {$newVersion}", [$file], $amend, $hooks);
            if ($result !== false) {
                $output->success('Release committed!');
                // Create tag
                if ($autoTag) {
                    $result = Git::tag('v' . $newVersion);
                    if ($result !== false) {
                        $output->success("Release tagged with success! New version: v{$newVersion}");
                    } else {
                        $output->error('An error occurred tagging the release!');

                        return Command::FAILURE;
                    }
                }
            } else {
                $output->error('An error occurred committing the release!');

                return Command::FAILURE;
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Generate markdown from changes.
     */
    protected function getMarkdownChanges(array $changes): string
    {
        $changelog = '';
        // Add all changes list to new changelog
        foreach ($changes as $type => $list) {
            if (empty($list)) {
                continue;
            }
            ksort($list);
            $changelog .= PHP_EOL . '### ' . $this->config->getTypeLabel($type) . "\n\n";
            foreach ($list as $scope => $items) {
                asort($items);
                if (is_string($scope) && !empty($scope)) {
                    // scope section
                    $changelog .= PHP_EOL . "##### {$scope}" . "\n\n";
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
     */
    protected function parseCommitHead(string $head, string $type): array
    {
        $parse = [
            'scope' => null,
            'description' => Utils::clean($head),
        ];

        $descriptionType = preg_quote(substr($parse['description'], 0, strlen($type)), '/');
        $parse['description'] = preg_replace('/^' . $descriptionType . '[:]?\s*/i', '', $parse['description']);
        $parse['description'] = preg_replace('/^\((.*?)\)[!]?[:]?\s*/', '**$1**: ', Utils::clean($parse['description']));
        $parse['description'] = preg_replace('/\s+/m', ' ', $parse['description']);

        // Set scope
        if (preg_match("/^\*\*(.*?)\*\*:(.*?)$/", $parse['description'], $match)) {
            $parse['scope'] = Utils::clean($match[1]);
            $parse['description'] = Utils::clean($match[2]);

            // Unify scope labels
            $parse['scope'] = preg_replace('/[_]+/m', ' ', $parse['scope']);
            $parse['scope'] = ucfirst($parse['scope']);
            $parse['scope'] = preg_replace('/((?<=\p{Ll})\p{Lu})|((?!\A)\p{Lu}(?>\p{Ll}))/u', ' $0', $parse['scope']);
            $parse['scope'] = preg_replace('/\.(php|md|json|txt|csv|js)($|\s)/', '', $parse['scope']);
            $parse['scope'] = Utils::clean($parse['scope']);
        }

        $parse['description'] = ucfirst($parse['description']);

        return $parse;
    }
}
