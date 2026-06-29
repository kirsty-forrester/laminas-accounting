<?php

namespace Accounting\ValueObject;

enum Direction: string
{
    case Debit = 'debit';   // destination — where money arrived
    case Credit = 'credit';  // source — where money came from
}