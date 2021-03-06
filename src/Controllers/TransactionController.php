<?php

namespace Paysera\Controllers;

use Paysera\Models\Transaction;
use Paysera\Repositories\RepositoryInterface;

class TransactionController
{
    /**
     * @var RepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var array
     */
    protected $config;

    /**
     * TransactionController constructor.
     *
     * @param RepositoryInterface $repository
     * @param array $config
     */
    public function __construct(RepositoryInterface $repository, array $config)
    {
        $this->transactionRepository = $repository;
        $this->config                = $config;
    }

    public function process($filename)
    {
        $this->transactionRepository->loadFromFile($filename);
        $transactions = $this->transactionRepository->getAll();

        $this->countCommissions($transactions);
    }

    private function countCommissions(array $transactions)
    {
        foreach ($transactions as $transaction) {
            if ($transaction->getTransactionType() == Transaction::CASH_IN) {
                $commission = $this->cashInCommission($transaction);
            } else {
                $commission = $this->cashOutCommission($transaction);
            }
            $this->printCommission($commission);
        }
    }

    private function cashInCommission(Transaction $transaction)
    {
        $commission     = $transaction->getTransactionAmount() * $this->config['inputCommissionPercent'];
        $convertedLimit = $this->convertCurrency($transaction, $this->config['inputCommissionLimitMax']);
        if ($commission > $convertedLimit) {
            return $convertedLimit;
        } else {
            return $commission;
        }
    }

    /**
     * Cash Out commission calculation for private and business users
     * @param Transaction $transaction
     * @return float|int
     */
    private function cashOutCommission(Transaction $transaction)
    {
        if ($transaction->getUserType() == 'private') {
            $date                      = new \DateTime($transaction->getDate());
            $week                      = $date->format('W');
            $year                      = $date->format('Y');
            $userTransactions          = $this->transactionRepository->getByField('userId', $transaction->getUserId());
            $transactionsPerWeek       = 0;
            $transactionsPerWeekAmount = 0;

            /** @var Transaction $userTransaction */
            foreach ($userTransactions as $userTransaction) {
                $currentDate = new \DateTime($userTransaction->getDate());
                if ($week == $currentDate->format('W') && $userTransaction->getTransactionType() == Transaction::CASH_OUT) {
                    if ($userTransaction->getId() == $transaction->getId()) {
                        break;
                    }
                    $diff=date_diff($currentDate,$date);
                    $days = $diff->format("%a");
                    if($year == $currentDate->format('Y') || $days < 7){
                        $transactionsPerWeek++;
                        $transactionsPerWeekAmount += $this->convertCurrency($userTransaction);
                    }
                }
            }
            /** private user's discount for cashout calculation */
            if ($transactionsPerWeek > $this->config['outputCommissionPrivateFreeTransactions']) {
                $commission = $transaction->getTransactionAmount() * $this->config['outputCommissionPercentPrivate'];
                return $commission;
            } else {
                if ($transactionsPerWeekAmount > $this->config['outputCommissionPrivateDiscount']) {
                    $commission = $transaction->getTransactionAmount() * $this->config['outputCommissionPercentPrivate'];
                    return $commission;
                } else {
                    $amount     = max($this->convertCurrency($transaction) + $transactionsPerWeekAmount - $this->config['outputCommissionPrivateDiscount'], 0);
                    $commission = $amount * $this->config['outputCommissionPercentPrivate'];
                    return $this->convertCurrency($transaction, $commission);
                }

            }
        } else {
            $commission     = $transaction->getTransactionAmount() * $this->config['outputCommissionPercentBusiness'];
            $convertedLimit = $this->convertCurrency($transaction, $this->config['outputCommissionBusinessLimitMin']);
            if ($commission < $convertedLimit) {
                return $convertedLimit;
            } else {
                return $commission;
            }
        }
    }

    /**
     * Converts transaction amount to EUR if $amount = -1
     * Converts $amount to transaction's currency  if $amount >= 0
     * @param Transaction $transaction
     * @param int $amount
     * @return float|int
     */
    private function convertCurrency(Transaction $transaction, $amount = -1)
    {
        if ($amount < 0) {
            $converted = $transaction->getTransactionAmount() / $this->config['currencyConversion'][$transaction->getCurrency()];
        } else {
            $converted = $amount * $this->config['currencyConversion'][$transaction->getCurrency()];
        }
        $fig       = pow(10, $this->config['commissionPrecision']);
        $converted = ceil($converted * $fig) / $fig;
        return $converted;
    }

    private function printCommission($commission)
    {
        fwrite(STDOUT, sprintf("%0.2f\n", $commission));
    }
}