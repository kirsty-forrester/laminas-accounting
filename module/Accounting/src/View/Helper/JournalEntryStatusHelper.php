<?php

namespace Accounting\View\Helper;

use Accounting\ValueObject\JournalEntryStatus;
use Laminas\View\Helper\AbstractHelper;

/**
 * Presentation for journal-entry statuses (badge colours). Kept out of the
 * JournalEntryStatus value object so the domain carries no UI concerns.
 *
 * Usage in templates: $this->journalEntryStatus()->colour($status)
 */
final class JournalEntryStatusHelper extends AbstractHelper
{
    public function __invoke(): self
    {
        return $this;
    }

    /** Bootstrap contextual class for a status badge (used as bg-{colour}). */
    public function colour(JournalEntryStatus $status): string
    {
        return match ($status) {
            JournalEntryStatus::Draft     => 'secondary',
            JournalEntryStatus::Submitted => 'info',
            JournalEntryStatus::Approved  => 'primary',
            JournalEntryStatus::Posted    => 'success',
            JournalEntryStatus::Voided    => 'dark',
        };
    }
}
