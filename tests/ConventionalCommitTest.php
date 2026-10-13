<?php

namespace Tests;

use ConventionalChangelog\Git\ConventionalCommit;
use PHPUnit\Framework\TestCase;

class ConventionalCommitTest extends TestCase
{
    /** @test */
    public function testHeaderInSubjectIsValid(): void
    {
        $commit = new ConventionalCommit("feat(api): add thing\n\nSome body text");

        $this->assertTrue($commit->isValid());
        $this->assertSame('feat', (string)$commit->getType());
        $this->assertSame('add thing', (string)$commit->getDescription());
    }

    /** @test */
    public function testHeaderLikeLineInBodyIsNotValid(): void
    {
        $this->assertFalse((new ConventionalCommit("Update readme\n\nNote: this is not conventional"))->isValid());
        $this->assertFalse((new ConventionalCommit("Merge branch 'x'\n\nfix: something in the body"))->isValid());
    }
}
