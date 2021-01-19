<?php

namespace ConventionalChangelog\Commit;

use ConventionalChangelog\Type\Stringable;

class Type implements Stringable
{
    /**
     * @var string
     */
    public $content;

    public function __construct(string $content)
    {
        $this->content = strtolower($content);
    }

    public function __toString(): string
    {
        return $this->content;
    }
}
