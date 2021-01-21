<?php

namespace ConventionalChangelog\Commit;

use ConventionalChangelog\Helper\Format;
use ConventionalChangelog\Type\Stringable;

class Scope implements Stringable
{
    /**
     * @var string
     */
    public $content;

    public function __construct(?string $content = '')
    {
        $this->content = (string)$content;
    }

    /**
     * Prettify.
     */
    public function toPrettyString(): string
    {
        $string = ucfirst($this->content);
        $string = preg_replace('/[_]+/m', ' ', $string);
        $string = preg_replace('/((?<=\p{Ll})\p{Lu})|((?!\A)\p{Lu}(?>\p{Ll}))/u', ' $0', $string);
        $string = preg_replace('/\.(php|md|json|txt|csv|js)($|\s)/', '', $string);
        $string = Format::clean($string);

        return $string;
    }

    public function __toString(): string
    {
        return $this->content;
    }
}
