<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\WalletTracker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ServiceDebitJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */

    public $fee;
    public String $reference;
    public Business $biz;
    public String $description;
    public function __construct($fee,$reference,Business $biz,$description)
    {
        $this->fee=$fee;
        $this->reference=$reference;
        $this->biz=$biz;
        $this->description=$description;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        WalletTracker::create([
            'reference' => $this->reference,
            'description' => $this->description,
            'amount' => $this->fee,
            'business_id' => $this->biz->id,
            'type' => 'debit',
            'pre_wallet' => $this->biz->wallet,
            'post_wallet' => $this->biz->wallet - $this->fee,
        ]);

        $this->biz->wallet-=$this->fee;
        $this->biz->save();
    }
}
