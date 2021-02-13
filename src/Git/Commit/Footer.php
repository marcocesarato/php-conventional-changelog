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
    protected $token;

    /**
     * Value.
     *
     * @var string
     */
    protected $value;

    /**
     * References.
     *
     * @var Reference[]
     */
    protected $references;

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

        $this->setReferences($refs);
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
     * Set issues references.
     *
     * @return Reference[]
     */
    public function setReferences(array $references): self
    {
        $this->references = array_unique($references);

        return $this;
    }

    /**
     * Get issues references.
     *
     * @return Reference[]
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
