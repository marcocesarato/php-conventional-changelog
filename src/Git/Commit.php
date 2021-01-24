<?php

namespace ConventionalChangelog\Git;

use ConventionalChangelog\Helper\Formatter;
use ConventionalChangelog\Type\Stringable;
use DateTime;

class Commit implements Stringable
{
    /**
     * Raw content.
     *
     * @var string
     */
    protected $raw;

    /**
     * Sha hash.
     *
     * @var string
     */
    protected $hash;

    /**
     * @var DateTime
     */
    protected $authorDate;

    /**
     * @var string
     */
    protected $authorName;

    /**
     * @var string
     */
    protected $authorEmail;

    /**
     * @var DateTime
     */
    protected $committerDate;

    /**
     * @var string
     */
    protected $committerName;

    /**
     * @var string
     */
    protected $committerEmail;

    public function __construct(string $commit = null)
    {
        // New commit or empty commit
        if (empty($commit)) {
            return;
        }

        $raw = Formatter::clean($commit);
        $this->setRaw($raw);
    }

    /**
     * From array.
     *
     * @throws \Exception
     */
    public function fromArray(array $array): self
    {
        if (isset($array['raw'])) {
            $this->setRaw($array['raw']);
        }
        if (isset($array['hash'])) {
            $this->setHash($array['hash']);
        }
        if (isset($array['authorName'])) {
            $this->setAuthorName($array['authorName']);
        }
        if (isset($array['authorEmail'])) {
            $this->setAuthorEmail($array['authorEmail']);
        }
        if (isset($array['authorDate'])) {
            $date = new DateTime($array['authorDate']);
            $this->setAuthorDate($date);
        }
        if (isset($array['committerName'])) {
            $this->setCommitterName($array['committerName']);
        }
        if (isset($array['committerEmail'])) {
            $this->setCommitterEmail($array['committerEmail']);
        }
        if (isset($array['committerDate'])) {
            $date = new DateTime($array['committerDate']);
            $this->setCommitterDate($date);
        }

        return $this;
    }

    /**
     * Check if is valid SHA-1.
     */
    protected function isValidHash(string $hash): bool
    {
        return (bool)preg_match('/^[0-9a-f]{40}$/i', $hash);
    }

    public function setRaw(string $raw): self
    {
        $this->raw = $raw;

        return $this;
    }

    public function setHash(string $hash): self
    {
        if ($this->isValidHash($hash)) {
            $this->hash = $hash;
        }

        return $this;
    }

    public function getRaw(): ?string
    {
        return $this->raw;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function getShortHash(): ?string
    {
        return substr($this->hash, 0, 6);
    }

    public function getAuthorDate(): DateTime
    {
        return $this->authorDate;
    }

    public function setAuthorDate(DateTime $authorDate): Commit
    {
        $this->authorDate = $authorDate;

        return $this;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(string $authorName): Commit
    {
        $this->authorName = $authorName;

        return $this;
    }

    public function getAuthorEmail(): ?string
    {
        return $this->authorEmail;
    }

    public function setAuthorEmail(string $authorEmail): Commit
    {
        $this->authorEmail = $authorEmail;

        return $this;
    }

    public function getCommitterDate(): DateTime
    {
        return $this->committerDate;
    }

    public function setCommitterDate(DateTime $committerDate): Commit
    {
        $this->committerDate = $committerDate;

        return $this;
    }

    public function getCommitterName(): ?string
    {
        return $this->committerName;
    }

    public function setCommitterName(string $committerName): Commit
    {
        $this->committerName = $committerName;

        return $this;
    }

    public function getCommitterEmail(): ?string
    {
        return $this->committerEmail;
    }

    public function setCommitterEmail(string $committerEmail): Commit
    {
        $this->committerEmail = $committerEmail;

        return $this;
    }

    public function __toString(): string
    {
        return (string)$this->getRaw();
    }
}
