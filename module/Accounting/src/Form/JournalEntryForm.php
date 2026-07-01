<?php

namespace Accounting\Form;

use Laminas\Form\Element\Collection;
use Laminas\Form\Element\Date;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

class JournalEntryForm extends Form implements InputFilterProviderInterface
{
    /** @param iterable $accounts Account models for each line's account <select> */
    public function __construct(iterable $accounts = [])
    {
        // We will ignore the name provided to the constructor
        parent::__construct('journal_entry');

        $this->add([
            'name' => 'journal_entry_id',
            'type' => Hidden::class,
        ]);

        $this->add([
            'name' => 'date',
            'type' => Date::class,
            'options' => [
                'label' => 'Date',
            ],
            'attributes' => [
                'value' => date('Y-m-d'),
            ],
        ]);

        $this->add([
            'name' => 'description',
            'type' => Text::class,
            'options' => [
                'label' => 'Description',
            ],
        ]);

        $this->add([
            'type' => Collection::class,
            'name' => 'lines',
            'options' => [
                'label' => 'Lines',
                'count' => 2,                       // start with two rows: a debit and a credit
                'should_create_template' => true,   // emit a __index__ template row for JS to clone
                'template_placeholder' => '__index__',
                'allow_add' => true,                // let client-side added rows bind on submit
                'target_element' => new JournalEntryLineFieldset($accounts),
            ],
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Post',
                'id'    => 'submitbutton',
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        // Deferred: line-level rules and the debits-equal-credits balance check
        // move here (or into a standalone InputFilter) in the extraction step.
        return [
            'date' => [
                'required' => true,
            ],
            'description' => [
                'required' => true,
                'filters' => [
                    ['name' => \Laminas\Filter\StripTags::class],
                    ['name' => \Laminas\Filter\StringTrim::class],
                ],
            ],
        ];
    }
}
