<?php

namespace Accounting\Report;

interface ReportInterface
{
    public function generate(Ledger $ledger): array;  // rows the view renders
}