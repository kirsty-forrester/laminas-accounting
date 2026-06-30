<?php

namespace Accounting\Controller;

use Accounting\Model\AccountRepositoryInterface;
use Laminas\Mvc\Controller\AbstractActionController;

class JournalController extends AbstractActionController
{
    public function __construct(private AccountRepositoryInterface $accountRepo) {}

    public function indexAction()
    {
    }

    public function addAction()
    {
        return ['accounts' => $this->accountRepo->all()];
    }
}