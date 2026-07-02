<?php

declare(strict_types=1);

namespace AccountingTest\View\Helper;

use Accounting\ValueObject\JournalEntryStatus;
use Accounting\View\Helper\JournalEntryStatusHelper;
use PHPUnit\Framework\TestCase;

final class JournalEntryStatusHelperTest extends TestCase
{
    private JournalEntryStatusHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new JournalEntryStatusHelper();
    }

    public function testInvokeReturnsSelfForMethodAccess(): void
    {
        $this->assertSame($this->helper, ($this->helper)());
    }

    /** @dataProvider colourProvider */
    public function testColour(JournalEntryStatus $status, string $expected): void
    {
        $this->assertSame($expected, $this->helper->colour($status));
    }

    public static function colourProvider(): array
    {
        return [
            [JournalEntryStatus::Draft, 'secondary'],
            [JournalEntryStatus::Submitted, 'info'],
            [JournalEntryStatus::Approved, 'primary'],
            [JournalEntryStatus::Posted, 'success'],
            [JournalEntryStatus::Voided, 'dark'],
        ];
    }
}