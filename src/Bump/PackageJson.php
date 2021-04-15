<?php

namespace ConventionalChangelog\Bump;

use ConventionalChangelog\Type\Bump;

class PackageJson extends Bump
{
    /**
     * {@inheritdoc}
     */
    protected $fileName = 'package.json';
    /**
     * {@inheritdoc}
     */
    protected $lockFiles = ['package.lock', 'yarn.lock', 'pnpm-lock.yaml'];
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
