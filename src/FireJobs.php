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

use Illuminate\Events\Dispatcher;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Jobs\Job;
use Symfony\Component\Debug\Exception\FatalThrowableError;

/**
 * Class FireJobs
 *
 * @author Jose Lorente <jose.lorente.martin@gmail.com>
 */
trait FireJobs
{

    /**
     * Process a job.
     * 
     * @param \Illuminate\Contracts\Queue\Job $job
     * @return void
     */
    protected function fireJob($job)
    {
        try {
            $this->raiseBeforeJobEvent($job);

            $job->fire();

            $this->raiseAfterJobEvent($job);
        } catch (Exception $e) {
            $this->handleException($job, $e);
        } catch (Throwable $e) {
            $this->handleException($job, new FatalThrowableError($e));
        }
    }

    /**
     * Raise the before queue job event.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseBeforeJobEvent($job)
    {
        $this->dispatchEvent(new JobProcessing(
                        $this->getConnectionName(), $job
        ));
    }

    /**
     * Raise the after queue job event.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @return void
     */
    protected function raiseAfterJobEvent($job)
    {
        $this->dispatchEvent(new JobProcessed(
                        $this->getConnectionName(), $job
        ));
    }

    /**
     * Handle an exception that occurred while processing a job.
     *
     * @param  \Illuminate\Queue\Jobs\Job  $queueJob
     * @param  \Exception  $e
     * @return void
     *
     * @throws \Exception
     */
    protected function handleException($queueJob, $e)
    {
        $this->raiseExceptionOccurredJobEvent($queueJob, $e);

        $queueJob->fail($e);

        throw $e;
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  \Exception  $e
     * @return void
     */
    protected function raiseExceptionOccurredJobEvent(Job $job, $e)
    {
        $this->dispatchEvent(new JobExceptionOccurred(
                        $this->getConnectionName(), $job, $e
        ));
    }

    /**
     * Dispatches the given event.
     * 
     * @param stdClass $event
     */
    protected function dispatchEvent($event)
    {
        $dispatcher = $this->getDispatcher();
        if ($dispatcher) {
            $dispatcher->dispatch($event);
        }
    }

    /**
     * Gets the events dispatcher.
     * 
     * @return Dispatcher|null
     */
    abstract public function getDispatcher();

    /**
     * Gets the queue connection name.
     * 
     * @return string
     */
    abstract public function getConnectionName();
}
