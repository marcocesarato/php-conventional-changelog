<?php

namespace ConventionalChangelog;

use ConventionalChangelog\Helper\Format;
use ConventionalChangelog\Helper\Git;
use ConventionalChangelog\Helper\SemanticVersion;
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
        $todayString = Format::getDateString($today);

        // First commit
        $firstCommit = Git::getFirstCommit();

        if (!$firstRelease) {
            $lastVersion = Git::getLastTag(); // Last version
            $lastVersionCommit = Git::getLastTagCommit(); // Last version commit
            $lastVersionDate = Git::getCommitDate($lastVersionCommit); // Last version date

            $bumpRelease = SemanticVersion::PATCH;

            if ($majorRelease) {
                $bumpRelease = SemanticVersion::MAJOR;
            } elseif ($minorRelease) {
                $bumpRelease = SemanticVersion::MINOR;
            } elseif ($patchRelease) {
                $bumpRelease = SemanticVersion::PATCH;
            } elseif ($preRelease) {
                $bumpRelease = SemanticVersion::RC;
            } elseif ($betaRelease) {
                $bumpRelease = SemanticVersion::BETA;
            } elseif ($alphaRelease) {
                $bumpRelease = SemanticVersion::ALPHA;
            } else {
                $autoBump = true;
            }

            // Generate new version code
            $semver = new SemanticVersion($lastVersion);
            $newVersion = $semver->bump($bumpRelease);
        }

        $nextVersion = $input->getOption('ver');
        if (!empty($nextVersion)) {
            $newVersion = $nextVersion;
        }
        $newVersion = preg_replace('#^v#i', '', $newVersion);

        if (empty($newVersion)) {
            $newVersion = '1.0.0';
        }

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
                    $todayString = Format::getDateString($today);
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
            foreach ($commitsRaw as $commitRaw) {
                $commit = new Commit\Conventional($commitRaw);

                // Not a conventional commit
                if (!$commit->isValid()) {
                    continue;
                }

                // Check ignored commit
                $ignore = false;
                foreach ($this->config->getIgnorePatterns() as $pattern) {
                    if (preg_match($pattern, $commit->getHeader())) {
                        $ignore = true;
                        break;
                    }
                }
                // Add commit
                if (!$ignore) {
                    $commits[] = new Commit\Conventional($commit);
                }
            }

            // Changes groups sorting
            $changes = ['breaking_changes' => []];
            foreach ($this->config->getTypes() as $key => $type) {
                $changes[$key] = [];
            }

            // Group all changes to lists by type
            $types = $this->config->getTypes();
            foreach ($commits as $commit) {
                if (in_array($commit->getType(), $types)) {
                    $itemKey = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '', $commit->getDescription()));
                    $breakingChanges = $commit->getBreakingChanges();
                    $type = (string)$commit->getType();
                    $scope = $commit->getScope()->toPrettyString();
                    $hash = $commit->getHash();
                    if (!empty($breakingChanges)) {
                        $type = 'breaking_changes';
                    }
                    $changes[$type][$scope][$itemKey][$hash] = $commit;
                }
            }

            // Remote url
            $url = Git::getRemoteUrl();
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
     *
     * @param Commit\Conventional[][][][]  $changes
     */
    protected function getMarkdownChanges(array $changes): string
    {
        $changelog = '';
        // Remote url
        $url = Git::getRemoteUrl();
        // Add all changes list to new changelog
        foreach ($changes as $type => $list) {
            if (empty($list)) {
                continue;
            }
            if ($type === 'breaking_changes') {
                $label = '⚠ BREAKING CHANGES';
            } else {
                $label = $this->config->getTypeLabel($type);
            }
            $changelog .= "\n### {$label}\n\n";
            ksort($list);
            foreach ($list as $scope => $items) {
                asort($items);
                if (is_string($scope) && !empty($scope)) {
                    // scope section
                    $changelog .= "\n##### {$scope}\n\n";
                }
                foreach ($items as $itemsList) {
                    $important = '';
                    $description = '';
                    $sha = '';
                    $references = '';
                    $shaGroup = [];
                    $refsGroup = [];
                    foreach ($itemsList as $item) {
                        $description = ucfirst($item->getDescription());
                        $refs = $item->getReferences();

                        if ($item->isImportant()) {
                            $important = '**';
                        }

                        if (!empty($refs)) {
                            foreach ($refs as $ref) {
                                $refsGroup[] = '[#' . $ref . "]({$url}/issue/{$ref})";
                            }
                        }
                        if (!empty($item->getHash())) {
                            $shaGroup[] = '[' . $item->getShortHash() . "]({$url}/commit/" . $item->getHash() . ')';
                        }
                    }
                    if (!empty($refsGroup)) {
                        $references = implode(', ', $refsGroup);
                    }
                    if (!empty($shaGroup)) {
                        $sha = '(' . implode(', ', $shaGroup) . ')';
                    }
                    $changelog .= "* {$important}{$description}{$important} {$references} {$sha}\n";
                }
            }
        }
        // Add version separator
        $changelog .= "\n---\n\n";

        return $changelog;
    }
}
