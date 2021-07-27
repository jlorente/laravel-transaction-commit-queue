<?php

/**
 * Part of the Laravel Transaction Commit Queue package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.
 *
 * @package    Laravel Transaction Commit Queue
 * @version    1.0.2
 * @author     Jose Lorente
 * @license    BSD License (3-clause)
 * @copyright  (c) 2019, Jose Lorente
 */

namespace Jlorente\Laravel\Queue\TransactionCommit;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Queue\QueueManager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Class TransactionCommitQueueServiceProvider
 *
 * @author Jose Lorente <jose.lorente.martin@gmail.com>
 */
class TransactionCommitQueueServiceProvider extends ServiceProvider
{

    /**
     * @inheritdoc
     */
    public function boot()
    {
        $this->registerTransactionCommitConnector($this->app['queue']);
        $this->registerTransactionCommitEvent();

        $this->publishes([
            __DIR__.'/config/transaction-commit-queue.php' => config_path('transaction-commit-queue.php')
        ], 'config');
    }

    /**
     * Registers the Transaction Commit event.
     */
    protected function registerTransactionCommitEvent()
    {
        Event::listen(TransactionCommitted::class, function ($event) {
            if ($event->connection->transactionLevel() === 0) {
                $this->runWorker($event->connection->getName());
            }
        });

        Event::listen(TransactionRolledBack::class, function ($event) {
            $this->clearWorker($event->connection->getName());
        });
    }

    /**
     * Registers the request end connector.
     */
    protected function registerTransactionCommitConnector(QueueManager $manager)
    {
        $manager->addConnector(TransactionCommitConnector::CONNECTION_NAME, function() {
            return new TransactionCommitConnector;
        });
    }

    /**
     * Clears the transaction commit queue.
     */
    protected function clearWorker($queue = null)
    {
        $this->createWorker()->clear($queue);
    }

    /**
     * Runs the transaction-commit queue until it is empty.
     */
    protected function runWorker($queue = null)
    {
        $this->createWorker()->daemon($queue);
    }

    /**
     * Creates the request end worker.
     * 
     * @return TransactionCommitWorker
     */
    protected function createWorker(): TransactionCommitWorker
    {
        return new TransactionCommitWorker(
                $this->app['queue'], $this->app['events'], $this->app[ExceptionHandler::class]
        );
    }

}
