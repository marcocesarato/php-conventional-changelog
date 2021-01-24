<?php

namespace ConventionalChangelog\Git\Commit;

use ConventionalChangelog\Type\Stringable;

class Body implements Stringable
{
    /**
     * @var string
     */
    public $content = '';

    public function __construct(?string $content = '')
    {
        $this->content = (string)$content;
    }

    public function __toString(): string
    {
        return ucfirst($this->content);
    }
}
