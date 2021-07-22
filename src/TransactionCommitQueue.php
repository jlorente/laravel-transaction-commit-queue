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

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Queue;
use Illuminate\Queue\Jobs\SyncJob;
use Illuminate\Support\Facades\DB;
use SplQueue;

/**
 * Class TransactionCommitQueue
 *
 * @author Jose Lorente <jose.lorente.martin@gmail.com>
 */
class TransactionCommitQueue extends Queue implements QueueContract
{

    use FireJobs;

    /**
     *
     * @var SplQueue 
     */
    protected $queues;

    /**
     * Get the size of the queue.
     *
     * @param  string|null  $queue
     * @return int
     */
    public function size($queue = null)
    {
        $queue = $this->getQueue($queue);
        return isset($this->queues[$queue]) ? $this->queues[$queue]->count() : 0;
    }

    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string|null  $queue
     * @return mixed
     *
     * @throws \Exception|\Throwable
     */
    public function push($job, $data = '', $queue = null)
    {
        $queueName = $this->getQueue($queue);

        $queueJob = $this->resolveJob($this->createPayload($job, $queueName, $data), $queue);
        $this->fireOrQueueJob($queue, $queueJob, $queueName);

        return true;
    }

    /**
     * @param string|null $queue
     * @param SyncJob $queueJob
     * @param string $queueName
     */
    private function fireOrQueueJob(?string $queue, SyncJob $queueJob, string $queueName)
    {
        if (DB::connection($queue)->transactionLevel() === 0 || config('transaction-commit-queue.dispatch_instantly')) {
            $this->fireJob($queueJob);
        } else {
            $this->pushToQueue($queueName, $queueJob);
        }
    }

    /**
     * Pushes the job to a concrete queue.
     * 
     * @param string $queue
     * @param Job $job
     * @return void
     */
    protected function pushToQueue($queue, Job $job)
    {
        if (isset($this->queues[$queue]) === false) {
            $this->queues[$queue] = new SplQueue();
        }

        $this->queues[$queue]->enqueue($job);
    }

    /**
     * Gets the events dispatcher.
     * 
     * @return Dispatcher|null
     */
    public function getDispatcher()
    {
        return $this->container->bound('events') ? $this->container['events'] : null;
    }

    /**
     * Gets the queue connection name.
     * 
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * Resolve a Sync job instance.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @return \Illuminate\Queue\Jobs\SyncJob
     */
    protected function resolveJob($payload, $queue)
    {
        return new SyncJob($this->container, $payload, $this->connectionName, $queue);
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string|null  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        //
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  \DateTimeInterface|\DateInterval|int  $delay
     * @param  string  $job
     * @param  mixed   $data
     * @param  string|null  $queue
     * @return mixed
     */
    public function later($delay, $job, $data = '', $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * Pop the next job off of the queue.
     *
     * @param  string|null  $queue
     * @return \Illuminate\Contracts\Queue\Job|null
     */
    public function pop($queue = null)
    {
        $queue = $this->getQueue($queue);

        return $this->popFromQueue($queue);
    }

    /**
     * Pops a job from a concrete queue.
     * 
     * @param string $queue
     */
    protected function popFromQueue($queue)
    {
        return isset($this->queues[$queue]) === false || $this->queues[$queue]->isEmpty() ? null : $this->queues[$queue]->dequeue();
    }

    /**
     * Get the queue or return the default.
     *
     * @param  string|null  $queue
     * @return string
     */
    public function getQueue($queue)
    {
        return $queue ?: DB::getName();
    }

}
