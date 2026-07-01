<?php

declare(strict_types=1);

namespace AccountingTest\ValueObject;

use Accounting\ValueObject\JournalEntryStatus;
use PHPUnit\Framework\TestCase;

final class JournalEntryStatusTest extends TestCase
{
    /**
     * @dataProvider allowedProvider
     */
    public function testCanTransitionToAllowedTarget(JournalEntryStatus $from, JournalEntryStatus $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
    }

    public static function allowedProvider(): array
    {
        return [
            'draft -> submitted'     => [JournalEntryStatus::Draft, JournalEntryStatus::Submitted],
            'draft -> voided'        => [JournalEntryStatus::Draft, JournalEntryStatus::Voided],
            'submitted -> approved'  => [JournalEntryStatus::Submitted, JournalEntryStatus::Approved],
            'submitted -> draft'     => [JournalEntryStatus::Submitted, JournalEntryStatus::Draft],
            'approved -> posted'     => [JournalEntryStatus::Approved, JournalEntryStatus::Posted],
            'posted -> voided'       => [JournalEntryStatus::Posted, JournalEntryStatus::Voided],
        ];
    }

    /**
     * @dataProvider forbiddenProvider
     */
    public function testCannotTransitionToForbiddenTarget(JournalEntryStatus $from, JournalEntryStatus $to): void
    {
        $this->assertFalse($from->canTransitionTo($to));
    }

    public static function forbiddenProvider(): array
    {
        return [
            'draft -> approved'   => [JournalEntryStatus::Draft, JournalEntryStatus::Approved],
            'draft -> posted'     => [JournalEntryStatus::Draft, JournalEntryStatus::Posted],
            'approved -> voided'  => [JournalEntryStatus::Approved, JournalEntryStatus::Voided],
            'posted -> draft'     => [JournalEntryStatus::Posted, JournalEntryStatus::Draft],
            'self transition'     => [JournalEntryStatus::Draft, JournalEntryStatus::Draft],
        ];
    }

    public function testVoidedIsTerminal(): void
    {
        $this->assertSame([], JournalEntryStatus::Voided->allowedNext());
    }
}
