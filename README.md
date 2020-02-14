Laravel Transaction Commit Queue
================================

A Laravel queue connector to process jobs on successful database transactions 
commits.

This connector is very similar to the "sync" connector with the difference that 
jobs are executed after the database transaction has been committed instead of 
instantly. 

It is useful for example when sending notifications that cause that other processes 
or third party applications read data from your database. When using database 
transactions and sending notifications, with another queue connectors there is 
no guarantee that this processes or third parties will find the data as you have 
set it when you sent the notification as the transaction might not has been 
committed yet. With this connector, the notifications will be sent on transaction 
commit event when the database transaction level reaches "0".

## Installation

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

With Composer installed, you can then install the extension using the following commands:

```bash
$ php composer.phar require jlorente/laravel-transaction-commit-queue
```

or add 

```json
...
    "require": {
        "jlorente/laravel-transaction-commit-queue": "*"
    }
```

to the ```require``` section of your `composer.json` file.

## Configuration

Register the ServiceProvider in your config/app.php service provider list.

config/app.php
```php
return [
    //other stuff
    'providers' => [
        //other stuff
        Jlorente\Laravel\Queue\TransactionCommitQueueServiceProvider::class,
    ];
];
```

Then add the driver to the application config queue file.

config\queue.php
```php
return [
    //other stuff
    'connections' => [
        //other stuff
        'transaction-commit' => [
            'driver' => 'transaction-commit',
        ],
    ],
];
```

## Usage

See the [Laravel documentation](https://laravel.com/docs/master/queues) to learn 
how to use jobs and queues.

The basic usage of this queue is like in the following example.

```php
DB::transaction(function() {
    // Do something

    dispatch(function() use ($model) {
        $model->notify();
    })->onConnection('transaction-commit');
});
```

Here, the job specified as callback will be delayed until the transaction is 
committed.

### Dispatching jobs on nested transactions

You can dispatch jobs to this queue inside nested transactions and the jobs will 
be processed after all the transactions have been resolved and the commit has 
been perfomed into the database.

```php
class ProcessExample {
    public function run() {
        DB::transaction(function() {
            // Do something more

            $this->nestedRun();
        });
    }

    public function nestedRun() {
        DB::transaction(function() {
            $model = new NotifiableExampleModel();

            // This job will be fired when all the transactions have been commited.
            dispatch(function() use ($model) {
                $model->notify();
            })->onConnection('transaction-commit');
        });
    }
}

$command = new ProcessExample();
$command->run();
```

In this example, the job is dispatched on the transaction created on nestedRun 
method, but this method is called by the run method from inside another 
transaction. The execution of the $model->notify() callback will be delayed 
until all the transactions have been committed.

### Multiple database connections

The queue driver will use the connection names defined in the database config 
file in order to create different queues for each connection.

If you don't specify the queue where to dispatch the job, the default queue will 
be used and the queue will be processed when the default connection reaches the 
transaction level of 0.

If you want to init a transaction in other database connection than the default 
one, remember to specify the queue with the connection name on the dispatched 
jobs to the transaction-commit-queue like in the following example.

```php
DB::connection('other-connection')->transaction(function() {
    // Do something
    $model = new NotifiableExampleModel();

    dispatch(function() use ($model) {
        $model->notify();
    })->onConnection('transaction-commit')->onQueue('other-connection');
});
```

## Further Considerations

If there isn't any open transaction on the database connection, the job with 
be fired instantly.

If a transaction is rolled back, all the pending jobs of the rolled back 
connection will be discarded.

Remember that [notifications](https://laravel.com/docs/master/notifications) can 
also be enqueued.

## License 

Copyright &copy; 2020 José Lorente Martín <jose.lorente.martin@gmail.com>.

Licensed under the BSD 3-Clause License. See LICENSE.txt for details.
