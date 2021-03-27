<?php

namespace ConventionalChangelog\Git;

use ConventionalChangelog\Configuration;
use ConventionalChangelog\Git\Commit\Description;
use ConventionalChangelog\Git\Commit\Reference;
use ConventionalChangelog\Git\Commit\Scope;
use ConventionalChangelog\Git\Commit\Type;
use ConventionalChangelog\Helper\Formatter;

class ConventionalCommit extends Commit
{
    /**
     * @var string
     */
    protected const PATTERN_HEADER = "/^(?<type>[a-z]+)(?<breaking_before>[!]?)(\((?<scope>.+)\))?(?<breaking_after>[!]?)[:][[:blank:]](?<description>.+)/iums";

    /**
     * Type.
     *
     * @var Type
     */
    protected $type;

    /**
     * Scope.
     *
     * @var Scope
     */
    protected $scope;

    /**
     * Is breaking change.
     *
     * @var bool
     */
    protected $isBreakingChange = false;

    /**
     * Description.
     *
     * @var Description
     */
    protected $description;

    public function __construct(?string $commit = null)
    {
        parent::__construct($commit);
        $this->parse();
    }

    /**
     * From commit.
     */
    public static function fromCommit(Commit $commit): self
    {
        return unserialize(
            preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen(self::class) . ':"' . self::class . '"', serialize($commit)),
            [self::class]
        );
    }

    /**
     * Check if is valid conventional commit.
     */
    public function isValid(): bool
    {
        return (bool)preg_match(self::PATTERN_HEADER, $this->raw);
    }

    public function getType(): Type
    {
        $type = $this->type;
        if ($this->isBreakingChange()) {
            $type = new Type(Configuration::BREAKING_CHANGES_TYPE);
        }

        return $type;
    }

    public function getScope(): Scope
    {
        return $this->scope;
    }

    public function hasScope(): bool
    {
        return !empty((string)$this->scope);
    }

    public function isBreakingChange(): bool
    {
        return $this->isBreakingChange;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getBreakingChanges(): array
    {
        $messages = [];
        foreach ($this->footers as $footer) {
            if ($footer->getToken() === 'breaking changes') {
                $messages[] = $footer->getValue();
            }
        }

        return $messages;
    }

    /**
     * Get issues references.
     *
     * @return Reference[]
     */
    public function getReferences(): array
    {
        $refs = [];
        foreach ($this->footers as $footer) {
            $refs = array_merge($footer->getReferences());
        }

        return array_unique($refs);
    }

    public function getHeader(): string
    {
        $header = $this->type;
        if ($this->hasScope()) {
            $header .= '(' . $this->scope . ')';
        }
        if ($this->isBreakingChange) {
            $header .= '!';
        }

        return $header . (': ' . $this->description);
    }

    public function setType(string $type): self
    {
        $this->type = new Type($type);

        return $this;
    }

    public function setScope(?string $scope): self
    {
        $this->scope = new Scope($scope);

        return $this;
    }

    public function setBreakingChange(bool $isBreakingChange): self
    {
        $this->isBreakingChange = $isBreakingChange;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = new Description($description);

        return $this;
    }

    /**
     * Parse raw commit.
     */
    protected function parse()
    {
        parent::parse();

        // Empty
        if (empty($this->raw)) {
            return;
        }

        // Not parsable
        if (!$this->isValid()) {
            return;
        }
        $header = $this->getSubject();

        $this->parseHeader($header);
    }

    /**
     * Parse header.
     */
    protected function parseHeader(string $header)
    {
        preg_match(self::PATTERN_HEADER, $header, $matches);
        $this->setType((string)$matches['type'])
            ->setScope((string)$matches['scope'])
            ->setBreakingChange(!empty($matches['breaking_before'] || !empty($matches['breaking_after'])))
            ->setDescription((string)$matches['description']);
    }

    public function __toString(): string
    {
        if (!empty($this->raw)) {
            return $this->raw;
        }

        $header = $this->getHeader();
        $message = $this->getMessage();
        $string = $header . "\n\n" . $message;

        return Formatter::clean($string);
    }
}
