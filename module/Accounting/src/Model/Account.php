<?php

namespace Accounting\Model;

use Accounting\ValueObject\AccountType;

class Account
{
    public function __construct(
        private ?int $accountId,
        private string $name,
        private AccountType $type,
    ) {}

    // Could turn this into a value object
    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): AccountType
    {
        return $this->type;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    public function changeType(AccountType $type): void
    {
        $this->type = $type;
    }
}