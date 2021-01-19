<?php

namespace ConventionalChangelog\Commit;

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

    public function __construct(string $token, string $value)
    {
        $this->token = $token;
        $this->value = $value;
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
        $refs = [];
        $value = $this->getValue();
        if ($value[0] === '#') {
            $values = explode(' ', $value);
            foreach ($values as $val) {
                $ref = ltrim($val, '#');
                if (is_numeric($ref)) {
                    $refs[] = $ref;
                }
            }
        }

        return array_unique($refs);
    }

    public function __toString(): string
    {
        return $this->token . ': ' . $this->value;
    }
}
