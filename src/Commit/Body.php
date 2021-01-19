<?php

namespace ConventionalChangelog\Commit;

use ConventionalChangelog\Type\Stringable;

class Body implements Stringable
{
    /**
     * @var string
     */
    public $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function __toString(): string
    {
        return $this->content;
    }
}
