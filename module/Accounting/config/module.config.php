<?php
namespace Accounting;

use Doctrine\ORM\EntityManagerInterface;
use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;
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
                        'controller' => Controller\AccountListController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true, // so /accounts itself matches
                'child_routes'  => [
                    'view' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'       => '/view/:id',
                            'constraints' => ['id' => '[0-9]+'],
                            'defaults'    => ['controller' => Controller\AccountListController::class,   'action' => 'view'],
                        ],
                    ],
                    'add' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/add',
                            'defaults' => ['controller' => Controller\AccountWriteController::class,  'action' => 'add'],
                        ],
                    ],
                    'edit' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'       => '/edit/:id',
                            'constraints' => ['id' => '[0-9]+'],
                            'defaults'    => ['controller' => Controller\AccountWriteController::class,  'action' => 'edit'],
                        ],
                    ],
                    'delete' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'       => '/delete/:id',
                            'constraints' => ['id' => '[0-9]+'],
                            'defaults'    => ['controller' => Controller\AccountDeleteController::class, 'action' => 'delete'],
                        ],
                    ],
                ],
            ],
            'journals' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/journal',
                    'defaults' => [
                        'controller' => Controller\JournalListController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true, // so /journal itself matches
                'child_routes'  => [
                    'view' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'       => '/view/:id',
                            'constraints' => ['id' => '[0-9]+'],
                            'defaults'    => ['controller' => Controller\JournalListController::class, 'action' => 'view'],
                        ],
                    ],
                    'add' => [
                        'type'    => Literal::class,
                        'options' => [
                            'route'    => '/add',
                            'defaults' => ['controller' => Controller\JournalWriteController::class, 'action' => 'add'],
                        ],
                    ],
                    'transition' => [
                        'type'    => Segment::class,
                        'options' => [
                            'route'       => '/transition/:id',
                            'constraints' => ['id' => '[0-9]+'],
                            'defaults'    => ['controller' => Controller\JournalWriteController::class, 'action' => 'transition'],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'controllers' => [
        'factories' => [
            Controller\AccountListController::class => ReflectionBasedAbstractFactory::class,
            Controller\AccountWriteController::class  => ReflectionBasedAbstractFactory::class,
            Controller\AccountDeleteController::class => ReflectionBasedAbstractFactory::class,
            Controller\JournalListController::class => ReflectionBasedAbstractFactory::class,
            Controller\JournalWriteController::class => ReflectionBasedAbstractFactory::class,
        ],
    ],

    'service_manager' => [
        'aliases' => [
            Model\AccountRepositoryInterface::class
            => Persistence\AccountRepository::class,
            Model\JournalEntryRepositoryInterface::class
            => Persistence\JournalEntryRepository::class,
            Model\AccountCommandInterface::class
            => Persistence\AccountCommand::class,
            Model\JournalEntryCommandInterface::class
            => Persistence\JournalEntryCommand::class,
        ],
        'factories' => [
            Service\Ledger::class
            => ReflectionBasedAbstractFactory::class,
            Service\JournalEntryLifecycle::class
            => ReflectionBasedAbstractFactory::class,
            Persistence\AccountRepository::class
            => Persistence\ReflectionBasedAbstractFactory::class,
            Persistence\JournalEntryRepository::class
            => Persistence\JournalEntryRepositoryFactory::class,
            Persistence\AccountCommand::class
            => ReflectionBasedAbstractFactory::class,
            Persistence\JournalEntryCommand::class
            => ReflectionBasedAbstractFactory::class,
            EntityManagerInterface::class
            => Persistence\EntityManagerFactory::class
        ],
    ],

    'view_helpers' => [
        'aliases' => [
            'accountType'       => View\Helper\AccountTypeHelper::class,
            'journalEntryStatus' => View\Helper\JournalEntryStatusHelper::class,
        ],
        'factories' => [
            View\Helper\AccountTypeHelper::class        => InvokableFactory::class,
            View\Helper\JournalEntryStatusHelper::class => InvokableFactory::class,
        ],
    ],

    'view_manager' => [
        'template_path_stack' => [
            'accounting' => __DIR__ . '/../view',
        ],
        // The split account controllers would otherwise resolve to
        // account-list/, account-write/, account-delete/ templates. Map those
        // names to the shared account/ view files instead.
        // TODO: I don't like this. Just move the files
        'template_map' => [
            'accounting/account-list/index'    => __DIR__ . '/../view/accounting/account/index.phtml',
            'accounting/account-list/view'     => __DIR__ . '/../view/accounting/account/view.phtml',
            'accounting/account-write/add'     => __DIR__ . '/../view/accounting/account/add.phtml',
            'accounting/account-write/edit'    => __DIR__ . '/../view/accounting/account/edit.phtml',
            'accounting/account-delete/delete' => __DIR__ . '/../view/accounting/account/delete.phtml',
            'accounting/journal-list/index'    => __DIR__ . '/../view/accounting/journal/index.phtml',
            'accounting/journal-list/view'     => __DIR__ . '/../view/accounting/journal/view.phtml',
            'accounting/journal-write/add'     => __DIR__ . '/../view/accounting/journal/add.phtml',
        ],
    ],
];
