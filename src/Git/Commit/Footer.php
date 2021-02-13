<?php

namespace ConventionalChangelog\Git\Commit;

use ConventionalChangelog\Type\Stringable;

class Footer implements Stringable
{
    /**
     * Token.
     *
     * @var string
     */
    public $token;

    /**
     * Value.
     *
     * @var string
     */
    public $value;

    /**
     * References.
     *
     * @var Reference[]
     */
    public $references;

    public function __construct(string $token, string $value)
    {
        $this->token = trim($token);
        $this->value = trim($value);

        $refs = [];
        if ($value[0] === '#') {
            $values = explode(' ', $value);
            foreach ($values as $val) {
                $ref = ltrim($val, '#');
                if (is_numeric($ref)) {
                    $refs[] = new Reference($ref);
                }
            }
        }

        $this->references = array_unique($refs);
    }

    public function getToken(): string
    {
        return strtolower($this->token);
    }

    public function getValue(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Get issues references.
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    public function __toString(): string
    {
        return $this->token . ': ' . $this->value;
    }
}
