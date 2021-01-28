<?php

namespace ConventionalChangelog;

use ConventionalChangelog\Git\ConventionalCommit;
use ConventionalChangelog\Git\Repository;
use ConventionalChangelog\Helper\Formatter;
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

        $nextVersion = $input->getOption('ver');
        $autoCommit = $input->getOption('commit'); // Commit once changelog is generated
        $autoCommitAll = $autoCommit || $input->getOption('commit-all'); // Commit all changes once changelog is generated
        $autoTag = !$input->getOption('no-tag'); // Tag release once is committed
        $amend = $input->getOption('amend'); // Amend commit
        $hooks = !$input->getOption('no-verify'); // Verify git hooks
        $fromDate = $input->getOption('from-date');
        $toDate = $input->getOption('to-date');
        $fromTag = $input->getOption('from-tag');
        $toTag = $input->getOption('to-tag');
        $history = $input->getOption('history');

        $firstRelease = $input->getOption('first-release');
        $alphaRelease = $input->getOption('alpha');
        $betaRelease = $input->getOption('beta');
        $preRelease = $input->getOption('rc');
        $patchRelease = $input->getOption('patch');
        $minorRelease = $input->getOption('minor');
        $majorRelease = $input->getOption('major');

        $autoCommit = $autoCommit || $autoCommitAll;
        $autoBump = false;

        if (empty($root) || !is_dir($root)) {
            $root = $this->config->getRoot();
        }

        // Set working directory
        chdir($root);

        if (!Repository::isInsideWorkTree()) {
            $output->error('Not a git repository');

            return Command::FAILURE;
        }

        // If have amend option enable commit
        if ($amend) {
            $autoCommit = true;
        }

        // Initialize changelogs
        $file = $this->config->getPath();
        $dirname = dirname($file);
        if (!is_file($file) && !is_dir($dirname)) {
            $file = $root . DIRECTORY_SEPARATOR . $file;
        }
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
        $todayString = Formatter::getDateString($today);

        // First commit
        $firstCommit = Repository::getFirstCommit();

        if (!$firstRelease) {
            $lastVersion = Repository::getLastTag(); // Last version
            $lastVersionCommit = Repository::getLastTagCommit(); // Last version commit
            $lastVersionDate = Repository::getCommitDate($lastVersionCommit); // Last version date

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

        if (!empty($nextVersion)) {
            $newVersion = $nextVersion;
            $autoBump = false;
        }
        $newVersion = preg_replace('#^v#i', '', $newVersion);

        if (empty($newVersion)) {
            $newVersion = '1.0.0';
        }

        $options = []; // Git retrieve options per version

        if ($history) {
            $changelogCurrent = ''; // Clean changelog file
            $tags = Repository::getTags();

            $previousTag = null;
            foreach ($tags as $key => $toTag) {
                $fromTag = $firstCommit;
                if (!empty($previousTag) && $key !== 0) {
                    $fromTag = $previousTag;
                }
                $options[$toTag] = [
                    'from' => $fromTag,
                    'to' => $toTag,
                    'date' => Repository::getCommitDate($toTag),
                    'options' => "{$fromTag}...{$toTag}",
                    'autoBump' => false,
                ];
                $previousTag = $toTag;
            }
            if ($autoCommit) {
                $options[$lastVersion] = [
                    'from' => $lastVersion,
                    'to' => $newVersion,
                    'date' => $today->format('Y-m-d'),
                    'options' => "{$lastVersion}...HEAD",
                    'autoBump' => false,
                ];
            }
            $options = array_reverse($options);
        } else {
            if ($firstRelease) {
                // Get all commits from the first one
                $additionalParams = "{$firstCommit}...HEAD";
                $lastVersion = $firstCommit;
                if (empty($fromTag)) {
                    $fromTag = $firstCommit;
                }
            } else {
                // Get latest commits from last version date
                $additionalParams = "{$lastVersion}...HEAD";
                if (empty($fromTag)) {
                    $fromTag = $lastVersion;
                }
            }

            // Clean ranges
            if ((!empty($fromDate) || !empty($toDate)) &&
                empty($fromTag) &&
                empty($toTag)) {
                $additionalParams = '';
            }

            // Tag range
            if (!empty($fromTag) ||
                !empty($toTag)) {
                if (empty($toTag)) {
                    $toTag = 'HEAD';
                }
                $additionalParams = "{$fromTag}...{$toTag}";
            }

            // Date range
            if (!empty($fromDate) ||
                !empty($toDate)) {
                if (!empty($fromDate)) {
                    $additionalParams .= ' --since="' . date('Y-m-d', strtotime($fromDate)) . '"';
                }
                if (!empty($toDate)) {
                    $time = strtotime($toDate);
                    $additionalParams .= ' --before="' . date('Y-m-d', $time) . '"';
                    $today->setTimestamp($time);
                    $todayString = Formatter::getDateString($today);
                }
            }

            $options[$lastVersion] = [
                'from' => $lastVersion,
                'to' => $newVersion,
                'date' => $today->format('Y-m-d'),
                'options' => $additionalParams,
                'autoBump' => $autoBump,
            ];
        }

        $summary = [];
        foreach ($this->config->getTypes() as $key => $type) {
            $summary[$type] = 0;
        }

        foreach ($options as $version => $params) {
            $commitsRaw = Repository::getCommits($params['options']);

            // Get all commits information
            $commits = [];
            foreach ($commitsRaw as $commitRaw) {
                $commit = ConventionalCommit::fromCommit($commitRaw);

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
                    $commits[] = $commit;
                }
            }

            // Changes groups sorting
            $changes = [];
            foreach ($this->config->getTypes() as $key => $type) {
                $changes[$type] = [];
            }

            // Group all changes to lists by type
            $types = $this->config->getAllowedTypes();
            foreach ($commits as $commit) {
                if (in_array($commit->getType(), $types)) {
                    $itemKey = $this->getItemKey($commit->getDescription());
                    $breakingChanges = $commit->getBreakingChanges();
                    $type = (string)$commit->getType();
                    $scope = $commit->getScope()->toPrettyString();
                    $hash = $commit->getHash();
                    if (!empty($breakingChanges)) {
                        foreach ($breakingChanges as $description) {
                            // Clone commit as breaking with different description message
                            $breakingCommit = new ConventionalCommit();
                            $breakingCommit->setType($type)
                                           ->setDescription($description)
                                           ->setScope($scope)
                                           ->setHash($hash);
                            $key = $this->getItemKey($description);
                            $changes['breaking_changes'][$scope][$key][$hash] = $breakingCommit;
                            $summary['breaking_changes']++;
                        }
                    }
                    $changes[$type][$scope][$itemKey][$hash] = $commit;
                    $summary[$type]++;
                }
            }

            if ($params['autoBump']) {
                $bumpRelease = SemanticVersion::PATCH;

                if ($summary['breaking_changes'] > 0) {
                    $bumpRelease = SemanticVersion::MAJOR;
                } elseif ($summary['feat'] > 0) {
                    $bumpRelease = SemanticVersion::MINOR;
                }

                $semver = new SemanticVersion($params['from']);
                $newVersion = $params['to'] = $semver->bump($bumpRelease);
            }

            // Remote url
            $url = Repository::getRemoteUrl();
            // Initialize changelogs
            $changelogNew .= "## [{$params['to']}]($url/compare/{$params['from']}...v{$params['to']}) ({$params['date']})\n\n";
            // Add all changes list to new changelog
            $changelogNew .= $this->getMarkdownChanges($changes);
        }

        // Print summary
        if (!empty($summary)) {
            $output->title('Summary');
            $elements = [];
            foreach ($summary as $type => $count) {
                if ($count > 0) {
                    $elements[] = $count . ' ' . $this->config->getTypeDescription($type);
                }
            }
            $output->listing($elements);
        }

        // Save new changelog prepending the current one
        file_put_contents($file, $mainHeader . "{$changelogNew}{$changelogCurrent}");
        $output->success('Changelog generated!');
        $output->writeln(" > Changelog file: {$file}");

        // Create commit
        if ($autoCommit) {
            if ($autoCommitAll) {
                Repository::addAll();
            }
            $result = Repository::commit("chore(release): {$newVersion}", [$file], $amend, $hooks);
            if ($result !== false) {
                $output->success('Release committed!');
                // Create tag
                if ($autoTag) {
                    $tag = $this->config->getTagPrefix() . $newVersion . $this->config->getTagSuffix();
                    $result = Repository::tag($tag);
                    if ($result !== false) {
                        $output->success("Release tagged with success! New version: {$tag}");
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
     * Generate item key for merge commit with similar description.
     */
    protected function getItemKey(string $string): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '', $string));
    }

    /**
     * Generate markdown from changes.
     *
     * @param ConventionalCommit[][][][]  $changes
     */
    protected function getMarkdownChanges(array $changes): string
    {
        $changelog = '';
        // Remote url
        $url = Repository::getRemoteUrl();
        // Add all changes list to new changelog
        foreach ($changes as $type => $list) {
            if (empty($list)) {
                continue;
            }
            $label = $this->config->getTypeLabel($type);
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
                    $changelog .= Formatter::clean("* {$important}{$description}{$important} {$references} {$sha}");
                    $changelog .= PHP_EOL;
                }
            }
        }
        // Add version separator
        $changelog .= "\n---\n\n";

        return $changelog;
    }
}
