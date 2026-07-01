<?php
namespace Accounting;

use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Laminas\Router\Http\Segment;
use Laminas\Router\Http\Literal;

return [
    'router' => [
        'routes' => [
            'accounts' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/accounts',
                    'defaults' => [
                        'controller' => Controller\AccountController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'account' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/accounts/[:action[/:id]]',
                    'constraints' => ['id' => '[0-9]+'],
                    'defaults' => [
                        'controller' => Controller\AccountController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'journals' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/journal',
                    'defaults' => [
                        'controller' => Controller\JournalController::class,
                        'action' => 'index',
                    ],
                ],
            ],
            'journal' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/journal/[:action[/:id]]',
                    'constraints' => ['id' => '[0-9]+'],
                    'defaults' => [
                        'controller' => Controller\JournalController::class,
                        'action' => 'index',
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\AccountController::class => ReflectionBasedAbstractFactory::class,
            Controller\JournalController::class => ReflectionBasedAbstractFactory::class,
        ],
    ],

    'service_manager' => [
        'aliases' => [
            Model\AccountRepositoryInterface::class
            => Persistence\AccountRepository::class,
            Model\JournalEntryRepositoryInterface::class
            => Persistence\JournalEntryRepository::class,
        ],
        'factories' => [
            Service\Ledger::class
            => Service\LedgerFactory::class,
            Persistence\AccountRepository::class
            => Persistence\AccountRepositoryFactory::class,
            Persistence\JournalEntryRepository::class
            => Persistence\JournalEntryRepositoryFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            'accounting' => __DIR__ . '/../view',
        ],
    ],
];
