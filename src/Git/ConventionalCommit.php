<?php

namespace ConventionalChangelog\Git;

use ConventionalChangelog\Configuration;
use ConventionalChangelog\Git\Commit\Description;
use ConventionalChangelog\Git\Commit\Footer;
use ConventionalChangelog\Git\Commit\Mention;
use ConventionalChangelog\Git\Commit\Reference;
use ConventionalChangelog\Git\Commit\Scope;
use ConventionalChangelog\Git\Commit\Type;
use ConventionalChangelog\Helper\Formatter;

class ConventionalCommit extends Commit
{
    protected const PATTERN_HEADER = "/^(?<type>[a-z]+)(?<breaking_before>[!]?)(\((?<scope>.+)\))?(?<breaking_after>[!]?)[:][[:blank:]](?<description>.+)/iums";
    protected const PATTERN_FOOTER = "/(?<token>^([a-z0-9_-]+|BREAKING[[:blank:]]CHANGES?))(?<value>([:][[:blank:]]|[:]?[[:blank:]][#](?=\w)).*?)$/iums";

    /**
     * Sha hash.
     *
     * @var string
     */
    protected $hash;

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

    /**
     * Footers.
     *
     * @var Footer[]
     */
    protected $footers = [];

    /**
     * User Mentions.
     *
     * @var Mention[]
     */
    protected $mentions = [];

    public function __construct(?string $commit = null)
    {
        parent::__construct($commit);
        $this->parse();
    }

    /**
     * Parse header.
     */
    protected function parseHeader(string $header)
    {
        preg_match(self::PATTERN_HEADER, $header, $matches);
        $this->setType((string)$matches['type'])
             ->setScope((string)$matches['scope'])
             ->setBreakingChange(!empty($matches['breaking_before'] || !empty($matches['breaking_after'])) ? true : false)
             ->setDescription((string)$matches['description']);
    }

    /**
     * Parse message.
     */
    protected function parseMessage(string $message)
    {
        $body = Formatter::clean($message);

        $mentions = [];
        if (preg_match_all('/(?:^|\s+)(?<mention>@(?<user>[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}))(?:$|\s+)/smi', $this->raw, $matches)) {
            foreach ($matches['user'] as $match) {
                $mentions[] = new Mention($match);
            }
        }

        $footers = [];
        if (preg_match_all(self::PATTERN_FOOTER, $body, $matches, PREG_SET_ORDER, 0)) {
            foreach ($matches as $match) {
                $footer = $match[0];
                $body = str_replace($footer, '', $body);
                $value = ltrim((string)$match['value'], ':');
                $value = Formatter::clean($value);
                $footers[] = new Footer((string)$match['token'], $value);
            }
        }
        $body = Formatter::clean($body);
        $this->setBody($body)
             ->setFooters($footers)
             ->setMentions($mentions);
    }

    /**
     * From commit.
     */
    public static function fromCommit(Commit $commit): self
    {
        return unserialize(
            preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen(__CLASS__) . ':"' . __CLASS__ . '"', serialize($commit)),
            [__CLASS__]
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

    /**
     * @return Footer[]
     */
    public function getFooters(): array
    {
        return $this->footers;
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

    /**
     * Set mentions.
     *
     * @param Mention[] $mentions
     *
     * @return $this
     */
    public function setMentions(array $mentions): self
    {
        $this->mentions = array_unique($mentions);

        return $this;
    }

    /**
     * Get mentions.
     *
     * @return Mention[]
     */
    public function getMentions(): array
    {
        return $this->mentions;
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
        $header .= ': ' . $this->description;

        return $header;
    }

    public function getMessage(): string
    {
        $footer = implode("\n", $this->footers);

        return $this->body . "\n\n" . $footer;
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

    public function setFooters(array $footers): self
    {
        $this->footers = $footers;

        return $this;
    }

    /**
     * Parse raw commit.
     */
    protected function parse()
    {
        // Empty
        if (empty($this->raw)) {
            return;
        }

        // Not parsable
        if (!$this->isValid()) {
            return;
        }

        $rows = explode("\n", $this->raw);
        $header = $rows[0];
        $message = '';
        // Get message
        foreach ($rows as $i => $row) {
            if ($i !== 0) {
                $message .= $row . "\n";
            }
        }
        $this->parseHeader($header);
        $this->parseMessage($message);
    }

    public function __wakeup()
    {
        $this->parse();
    }

    public function __toString(): string
    {
        $header = $this->getHeader();
        $message = $this->getMessage();
        $string = $header . "\n\n" . $message;

        return Formatter::clean($string);
    }
}
