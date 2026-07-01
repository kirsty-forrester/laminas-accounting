<?php

namespace Accounting\Form;

use Accounting\ValueObject\Direction;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;

class JournalEntryLineFieldset extends Fieldset
{
    /**
     * @param iterable $accounts Account models used
     * to populate the account <select>
     */
    public function __construct(iterable $accounts = [])
    {
        parent::__construct('line');

        $this->add([
            'name' => 'journal_entry_line_id',
            'type' => Hidden::class,
        ]);

        $accountOptions = [];

        foreach ($accounts as $account) {
            $accountOptions[$account->getAccountId()] = $account->getName();
        }

        $this->add([
            'name' => 'account_id',
            'type' => Select::class,
            'options' => [
                'label' => 'Account',
                'empty_option' => '-- Select Account --',
                'value_options' => $accountOptions,
            ],
        ]);

        $this->add([
            'name' => 'debit',
            'type' => Text::class,
            'options' => [
                'label' => 'Debit',
            ],
            'attributes' => [
                'placeholder' => '0.00',
                'class'       => 'form-control debit-input',
            ],
        ]);

        $this->add([
            'name' => 'credit',
            'type' => Text::class,
            'options' => [
                'label' => 'Credit',
            ],
            'attributes' => [
                'placeholder' => '0.00',
                'class'       => 'form-control credit-input',
            ],
        ]);
    }
}
