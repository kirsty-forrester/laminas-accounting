<?php

namespace Accounting\Model;

interface AccountCommandInterface
{
    /**
     * Persist a new account in the system.
     *
     * @param Account $account The account to insert; may or may not have an identifier.
     * @return Account The inserted account, with identifier.
     */
    public function insertAccount(Account $account);

    /**
     * Update an existing account in the system.
     *
     * @param Account $account The account to update; must have an identifier.
     * @return Account The updated account.
     */
    public function updateAccount(Account $account);

    /**
     * Delete an account from the system.
     *
     * @param Account $account The account to delete.
     * @return bool
     */
    public function deleteAccount(Account $account);
}