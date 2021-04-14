<?php

namespace ConventionalChangelog\Bump;

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
}
