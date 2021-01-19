<?php

namespace ConventionalChangelog\Commit;

use ConventionalChangelog\Helper\Format;
use ConventionalChangelog\Type\Stringable;

class Parser implements Stringable
{
    protected const PATTERN_HEADER = "/^(?'type'[a-z]+)(\((?'scope'.+)\))?(?'important'[!]?)[:][[:blank:]](?'description'.+)/iums";
    protected const PATTERN_FOOTER = "/(?'token'^([a-z0-9_-]+|BREAKING[[:blank:]]CHANGES?))(?'value'([:][[:blank:]]|[[:blank:]]\#(?=\w)).*?)$/iums";

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

    public function __construct(string $commit)
    {
        $this->raw = Format::clean($commit);

        if (!$this->isValid()) {
            return;
        }

        $rows = explode("\n", $commit);
        $count = count($rows);
        // Commit info
        $this->hash = $rows[$count - 1];
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

    /**
     * Parse header.
     */
    protected function parseHeader(string $header)
    {
        preg_match(self::PATTERN_HEADER, $header, $matches);
        $this->type = new Type($matches['type']);
        $this->scope = new Scope($matches['scope']);
        $this->important = !empty($matches['important']) ? true : false;
        $this->description = new Description($matches['description']);
    }

    /**
     * Parse message.
     */
    protected function parseMessage(string $message)
    {
        $body = Format::clean($message);
        if (preg_match_all(self::PATTERN_FOOTER, $body, $matches, PREG_SET_ORDER, 0)) {
            foreach ($matches as $match) {
                $footer = $match[0];
                $body = str_replace($footer, '', $body);
                $value = ltrim($match['value'], ':');
                $this->footers[] = new Footer($match['token'], $value);
            }
        }
        $body = Format::clean($body);
        $this->body = new Body($body);
    }

    public function isValid(): bool
    {
        return preg_match(self::PATTERN_HEADER, $this->raw);
    }

    public function getRaw(): string
    {
        return $this->raw;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getShortHash(): string
    {
        return substr($this->hash, 0, 6);
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getScope(): Scope
    {
        return $this->scope;
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

    public function getHeader()
    {
        $header = $this->type;
        if (!empty((string)$this->scope)) {
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

    public function __toString(): string
    {
        $header = $this->getHeader();
        $message = $this->getMessage();
        $string = $header . "\n\n" . $message;

        return Format::clean($string);
    }
}
