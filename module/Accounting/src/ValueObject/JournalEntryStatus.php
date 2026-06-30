<?php

enum JournalEntryStatus: string
{
    case Draft     = 'draft';
    case Submitted = 'submitted';
    case Approved  = 'approved';
    case Posted    = 'posted';
    case Voided    = 'voided';
}