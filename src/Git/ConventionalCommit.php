<?php

namespace ConventionalChangelog\Git;

use ConventionalChangelog\Git\Commit\Body;
use ConventionalChangelog\Git\Commit\Description;
use ConventionalChangelog\Git\Commit\Footer;
use ConventionalChangelog\Git\Commit\Scope;
use ConventionalChangelog\Git\Commit\Type;
use ConventionalChangelog\Helper\Formatter;

class ConventionalCommit extends Commit
{
    protected const PATTERN_HEADER = "/^(?'type'[a-z]+)(\((?'scope'.+)\))?(?'important'[!]?)[:][[:blank:]](?'description'.+)/iums";
    protected const PATTERN_FOOTER = "/(?'token'^([a-z0-9_-]+|BREAKING[[:blank:]]CHANGES?))(?'value'([:][[:blank:]]|[:]?[[:blank:]][#](?=\w)).*?)$/iums";

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
     * Important.
     *
     * @var bool
     */
    protected $important = false;

    /**
     * Description.
     *
     * @var Description
     */
    protected $description;

    /**
     * @var Body
     */
    protected $body;

    /**
     * Footers.
     *
     * @var Footer[]
     */
    protected $footers = [];

    public function __construct(?string $commit = null)
    {
        parent::__construct($commit);

        // New commit or empty commit
        if (empty($commit)) {
            return;
        }

        // Not parsable
        if (!$this->isValid()) {
            return;
        }

        $this->__wakeup();
    }

    /**
     * Parse header.
     */
    protected function parseHeader(string $header)
    {
        preg_match(self::PATTERN_HEADER, $header, $matches);
        $this->setType((string)$matches['type'])
             ->setScope((string)$matches['scope'])
             ->setImportant(!empty($matches['important']) ? true : false)
             ->setDescription((string)$matches['description']);
    }

    /**
     * Parse message.
     */
    protected function parseMessage(string $message)
    {
        $body = Formatter::clean($message);
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
             ->setFooters($footers);
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
        return $this->type;
    }

    public function getScope(): Scope
    {
        return $this->scope;
    }

    public function hasScope(): bool
    {
        return !empty((string)$this->scope);
    }

    public function isImportant(): bool
    {
        return $this->important;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function getBody(): Body
    {
        return $this->body;
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
     */
    public function getReferences(): array
    {
        $refs = [];
        foreach ($this->footers as $footer) {
            $refs = array_merge($footer->getReferences());
        }

        return array_unique($refs);
    }

    public function getHeader()
    {
        $header = $this->type;
        if ($this->hasScope()) {
            $header .= '(' . $this->scope . ')';
        }
        if ($this->important) {
            $header .= '!';
        }
        $header .= ': ' . $this->description;

        return $header;
    }

    public function getMessage()
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

    public function setImportant(bool $important): self
    {
        $this->important = $important;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = new Description($description);

        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = new Body($body);

        return $this;
    }

    public function setFooters(array $footers): self
    {
        $this->footers = $footers;

        return $this;
    }

    public function __wakeup()
    {
        $rows = explode("\n", $this->raw);
        $count = count($rows);
        // Commit info
        $hash = trim($rows[$count - 1]);
        $this->setHash($hash);

        $header = $rows[0];
        $message = '';
        // Get message
        foreach ($rows as $i => $row) {
            if ($i !== 0 && $i !== $count) {
                $message .= $row . "\n";
            }
        }
        $this->parseHeader($header);
        $this->parseMessage($message);
    }

    public function __toString(): string
    {
        $header = $this->getHeader();
        $message = $this->getMessage();
        $string = $header . "\n\n" . $message;

        return Formatter::clean($string);
    }
}
