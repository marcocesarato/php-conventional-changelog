<?php

namespace ConventionalChangelog\Git\Commit;

use ConventionalChangelog\Type\Stringable;

class Reference implements Stringable
{
    /**
     * @var int
     */
    protected $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return '#' . $this->id;
    }
}
