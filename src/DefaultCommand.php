<?php

namespace ConventionalChangelog;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DefaultCommand extends Command
{
    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('changelog')
            ->setDescription('Generate changelogs and release notes from a project\'s commit messages' .
                'and metadata and automate versioning with semver.org and conventionalcommits.org')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Define the path directory where generate changelog', getcwd()),
                new InputOption('commit', 'c', InputOption::VALUE_NONE, 'Commit the new release once changelog is generated'),
                new InputOption('first-release', null, InputOption::VALUE_NONE, 'Run at first release (if --ver isn\'t specified version code it will be 1.0.0)'),
                new InputOption('from-date', null, InputOption::VALUE_REQUIRED, 'Get commits from specified date [YYYY-MM-DD]'),
                new InputOption('to-date', null, InputOption::VALUE_REQUIRED, 'Get commits last tag date (or specified on --from-date) to specified date [YYYY-MM-DD]'),
                new InputOption('major', 'maj', InputOption::VALUE_NONE, 'Major release (important changes)'),
                new InputOption('minor', 'min', InputOption::VALUE_NONE, 'Minor release (add functionality)'),
                new InputOption('patch', 'p', InputOption::VALUE_NONE, 'Patch release (bug fixes) [default]'),
                new InputOption('ver', null, InputOption::VALUE_REQUIRED, 'Define the next release version code (semver)'),
                new InputOption('history', null, InputOption::VALUE_NONE, 'Generate the entire history of changes of all releases'),
                new InputOption('no-chores', null, InputOption::VALUE_NONE, 'Exclude chores type from changelog'),
                new InputOption('no-refactor', null, InputOption::VALUE_NONE, 'Exclude refactor type from changelog'),
            ]);
    }

    /**
     * Execute.
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $changelog = new Changelog();
        $outputStyle = new SymfonyStyle($input, $output);

        return $changelog->generate($input, $outputStyle);
    }
}
