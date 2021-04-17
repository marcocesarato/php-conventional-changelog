<?php

namespace ConventionalChangelog\PackageBump;

use ConventionalChangelog\Helper\ShellCommand;
use ConventionalChangelog\Type\PackageBump;

class ComposerJson extends PackageBump
{
    /**
     * {@inheritdoc}
     */
    protected $fileName = 'composer.json';
    /**
     * {@inheritdoc}
     */
    protected $lockFiles = ['composer.lock'];
    /**
     * {@inheritdoc}
     */
    protected $fileType = 'json';

    /**
     * {@inheritdoc}
     */
    public function getVersion(): ?string
    {
        return $this->content->version;
    }

    /**
     * {@inheritdoc}
     */
    public function setVersion(string $version): self
    {
        $this->content->version = $version;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function save(): self
    {
        parent::save();
        if (ShellCommand::exists('composer')) {
            exec('cd ' . escapeshellarg($this->getPath()) . ' && composer update');
        }

        return $this;
    }
}
