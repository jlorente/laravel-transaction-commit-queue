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
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Queue\QueueManager;

/**
 * Class TransactionCommitWorker
 *
 * @author Jose Lorente <jose.lorente.martin@gmail.com>
 */
class TransactionCommitWorker
{

    use FireJobs;

    /**
     * The queue manager instance.
     *
     * @var \Illuminate\Queue\QueueManager
     */
    protected $manager;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The exception handler instance.
     *
     * @var \Illuminate\Contracts\Debug\ExceptionHandler
     */
    protected $exceptions;

    /**
     * Create a new queue worker.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  \Illuminate\Contracts\Debug\ExceptionHandler  $exceptions
     * @return void
     */
    public function __construct(QueueManager $manager,
            Dispatcher $events,
            ExceptionHandler $exceptions)
    {
        $this->events = $events;
        $this->manager = $manager;
        $this->exceptions = $exceptions;
    }

    /**
     * Runs the worker.
     */
    public function daemon($queue = null)
    {
        $connection = $this->manager->connection(TransactionCommitConnector::CONNECTION_NAME);

        while (!is_null($job = $connection->pop($queue))) {
            $this->fireJob($job);
        }
    }

    /**
     * Runs the worker without firing the jobs.
     */
    public function clear($queue = null)
    {
        $connection = $this->manager->connection(TransactionCommitConnector::CONNECTION_NAME);

        while (!is_null($job = $connection->pop($queue))) {
            
        }
    }

    /**
     * Gets the events dispatcher.
     * 
     * @return Dispatcher|null
     */
    public function getDispatcher()
    {
        return $this->events;
    }

    /**
     * Gets the queue connection name.
     * 
     * @return string
     */
    public function getConnectionName()
    {
        return TransactionCommitConnector::CONNECTION_NAME;
    }

}
