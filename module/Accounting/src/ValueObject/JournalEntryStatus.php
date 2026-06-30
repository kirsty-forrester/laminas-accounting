<?php

namespace Accounting\ValueObject;

enum JournalEntryStatus: string
{
    case Draft     = 'draft';
    case Submitted = 'submitted';
    case Approved  = 'approved';
    case Posted    = 'posted';
    case Voided    = 'voided';

    /** @return self[] */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft     => [self::Submitted, self::Voided],
            self::Submitted => [self::Approved, self::Draft],
            self::Approved  => [self::Posted, self::Draft],
            self::Posted    => [self::Voided],
            self::Voided    => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedNext(), true);
    }
}