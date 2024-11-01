<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;

class CheckTransactionExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:check-expiry';

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
        // $expiredTransactions = Transaction::where('status', 'pending')
        //                                   ->where('expire_time', '<', now())
        //                                   ->get();

        // $this->info($expiredTransactions);

        // foreach ($expiredTransactions as $transaction) {
        //     $transaction->status = 'failed';
        //     $transaction->save();
        //     // Optional: tambah log atau notifikasi
        //     $this->info("Transaksi Invoice {$transaction->invoice} telah expire.");
        // }

        // $this->info("Pengecekan transaksi expire selesai.");
    }
}
