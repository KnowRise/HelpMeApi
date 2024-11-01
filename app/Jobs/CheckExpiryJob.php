<?php

namespace App\Jobs;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckExpiryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transaction;

    /**
     * Create a new job instance.
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Running CheckExpiryJob for transaction ID: " . $this->transaction->id);

        $transaction = Transaction::find($this->transaction->id);

        if ($transaction && $transaction->status == 'pending' && $transaction->expire_time <= now()) {
            $transaction->status = 'failed';
            $transaction->save();
            Log::info("Transaction ID " . $transaction->id . " has been marked as failed due to expiry.");
        } else {
            Log::info("Transaction ID " . $transaction->id . " was not expired or not in pending status.");
        }
    }

}
