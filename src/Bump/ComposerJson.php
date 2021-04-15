<?php

namespace ConventionalChangelog\Bump;

use ConventionalChangelog\Helper\ShellCommand;
use ConventionalChangelog\Type\Bump;

class ComposerJson extends Bump
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
