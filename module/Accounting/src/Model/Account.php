<?php

namespace Accounting\Model;

use Accounting\ValueObject\AccountType;

class Account
{
    private ?int $accountId = null;
    private ?string $name = null;
    private ?AccountType $type = null;

    public function getAccountId(): ?int
    {
        return $this->accountId;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getType(): ?AccountType
    {
        return $this->type;
    }

    public function setAccountId(int $accountId): void
    {
        $this->accountId = $accountId;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setType(AccountType $type): void
    {
        $this->type = $type;
    }
}