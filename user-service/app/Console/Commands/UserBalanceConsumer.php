<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Models\User;

class UserBalanceConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:user-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $config = config('rabbitmq');
        $connection = new AMQPStreamConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password']
        );

        $channel = $connection->channel();
        $channel->queue_declare('user_balance_queue', false, true, false, false);
        $channel->queue_bind('user_balance_queue', $config['exchange'], 'transaction.approved');

        echo " [*] Waiting for approved transactions.\n";

        $callback = function (AMQPMessage $msg) {
            $transaction = json_decode($msg->body, true);
            echo " [x] Updating balance for user: " . $transaction['user_id'] . "\n";

            $user = User::find($transaction['user_id']);

            if ($transaction['type'] === 'deposit') {
                $user->balance += $transaction['amount'];
            } elseif ($transaction['type'] === 'withdrawal') {
                $user->balance -= $transaction['amount'];
            }elseif ($transaction['type'] === 'transfer') {
                $user->balance -= $transaction['amount'];
            }
            $user->save();
        };

        $channel->basic_consume('user_balance_queue', '', false, true, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}
