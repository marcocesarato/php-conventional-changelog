<?php

namespace Tests;

use ConventionalChangelog\Helper\CalendarVersion;
use PHPUnit\Framework\TestCase;

class CalendarVersionTest extends TestCase
{
    /** @test */
    public function testFirstReleaseOfDayStartsAtZero(): void
    {
        $version = CalendarVersion::generate([], CalendarVersion::DEFAULT_FORMAT, new \DateTimeImmutable('2026-09-06'));

        $this->assertEquals('2026.09.06.0', $version);
    }

    /** @test */
    public function testPatchIncrementsForReleasesOnSameDay(): void
    {
        $versions = ['2026.09.05.4', '2026.09.06.0', '2026.09.06.2'];

        $version = CalendarVersion::generate($versions, CalendarVersion::DEFAULT_FORMAT, new \DateTimeImmutable('2026-09-06'));

        $this->assertEquals('2026.09.06.3', $version);
    }

    /** @test */
    public function testCustomFormatIsSupported(): void
    {
        $version = CalendarVersion::generate(['26.09.0'], 'YY.MM.PATCH', new \DateTimeImmutable('2026-09-06'));

        $this->assertEquals('26.09.1', $version);
    }

    /** @test */
    public function testFormatRequiresPatchToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CalendarVersion::generate([], 'YYYY.MM.DD', new \DateTimeImmutable('2026-09-06'));
    }
}
