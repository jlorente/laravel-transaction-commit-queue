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

use Illuminate\Queue\Connectors\ConnectorInterface;

/**
 * Class TransactionCommitConnector
 *
 * @author Jose Lorente <jose.lorente.martin@gmail.com>
 */
class TransactionCommitConnector implements ConnectorInterface
{

    const CONNECTION_NAME = 'transaction-commit';

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Illuminate\Contracts\Queue\Queue
     */
    public function connect(array $config)
    {
        return new TransactionCommitQueue;
    }

}
