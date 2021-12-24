<?php

namespace Paysera\Repositories;

use Paysera\Models\Transaction;

class TransactionRepositoryTest extends \PHPUnit_Framework_TestCase
{

    public function testGetAll()
    {
        $transaction = new Transaction();
        $transaction->setDate("2016-01-05");
        $transaction->setUserId("1");
        $transaction->setUserType("private");
        $transaction->setTransactionType("deposite");
        $transaction->setTransactionAmount("200.00");
        $transaction->setCurrency("EUR");

        $repo = new TransactionRepository();

        $repo->add($transaction);

        self::assertEquals([$transaction], $repo->getAll());

    }

}
