<?php

namespace Accounting\Controller;

use Accounting\Model\AccountCommandInterface;
use Accounting\Model\AccountRepositoryInterface;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use InvalidArgumentException;

class AccountDeleteController extends AbstractActionController
{
    public function __construct(
        private AccountCommandInterface $command,
        private AccountRepositoryInterface $repository
    ){}

    // TODO: Stop or handle deletion when an account has journal entries
    public function deleteAction()
    {
        $id = $this->params()->fromRoute('id');
        if (! $id) {
            return $this->redirect()->toRoute('accounts');
        }

        try {
            $account = $this->repository->find($id);
        } catch (InvalidArgumentException $ex) {
            return $this->redirect()->toRoute('accounts');
        }

        $request = $this->getRequest();
        if (! $request->isPost()) {
            return new ViewModel(['account' => $account]);
        }

        if ($id != $request->getPost('id')
            || 'Delete' !== $request->getPost('confirm', 'no')
        ) {
            return $this->redirect()->toRoute('accounts');
        }

        $account = $this->command->deleteAccount($account);

        return $this->redirect()->toRoute('accounts');
    }
}