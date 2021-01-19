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

    public function __toString(): string
    {
        return $this->token . ': ' . $this->value;
    }
}
