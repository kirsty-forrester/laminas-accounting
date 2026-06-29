<?php

namespace Accounting\Form;

use Accounting\ValueObject\AccountType;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Submit;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

class AccountForm extends Form implements InputFilterProviderInterface
{
    public function __construct($name = null)
    {
        // We will ignore the name provided to the constructor
        parent::__construct('account');

        $this->add([
            'name' => 'account_id',
            'type' => Hidden::class,
        ]);

        $this->add([
            'name' => 'name',
            'type' => Text::class,
            'options' => [
                'label' => 'Name',
            ],
        ]);

        $this->add([
            'name' => 'account_type',
            'type' => Select::class,
            'options' => [
                'label' => 'Account Type',
                'value_options' => array_combine(
                    array_column(AccountType::cases(), 'value'),
                    array_map(fn(AccountType $t) => ucfirst($t->value), AccountType::cases()),
                ),
            ]
        ]);

        $this->add([
            'name' => 'submit',
            'type' => Submit::class,
            'attributes' => [
                'value' => 'Go',
                'id'    => 'submitbutton',
            ],
        ]);
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'name' => [
                'required' => true,
                'filters' => [
                    ['name' => \Laminas\Filter\StripTags::class],
                    ['name' => \Laminas\Filter\StringTrim::class],
                ],
                'validators' => [
                    [
                        'name' => \Laminas\Validator\StringLength::class,
                        'options' => [
                            'encoding' => 'UTF-8',
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ],
            'account_type' => [
                'required' => true,
                'validators' => [
                    [
                        'name' => \Laminas\Validator\InArray::class,
                        'options' => [
                            'haystack' => array_column(AccountType::cases(), 'value'),
                        ],
                    ],
                ],
            ],
        ];
    }
}