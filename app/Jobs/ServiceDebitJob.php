<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\PndL;
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
    public $providerCost;
    public $providerName;
    public String $reference;
    public Business $biz;
    public String $description;
    public function __construct($fee, $reference, Business $biz, $description, $providerCost = 0, $providerName = null)
    {
        $this->fee=$fee;
        $this->reference=$reference;
        $this->biz=$biz;
        $this->description=$description;
        $this->providerCost = $providerCost ?? 0;
        $this->providerName = $providerName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->biz->refresh();

        $existing = WalletTracker::where('reference', $this->reference)->first();
        if ($existing) {
            return;
        }

        $vatRate = (float) env('VAT_RATE', 0.075);
        $vatAmount = round(((float) $this->fee) * $vatRate, 2);
        $totalDebit = (float) $this->fee + (float) $vatAmount;

        WalletTracker::create([
            'reference' => $this->reference,
            'description' => $this->description,
            'amount' => $totalDebit,
            'business_id' => $this->biz->id,
            'type' => 'debit',
            'pre_wallet' => $this->biz->wallet,
            'post_wallet' => $this->biz->wallet - $totalDebit,
        ]);

        if ($vatAmount > 0) {
            WalletTracker::create([
                'reference' => $this->reference . "_vat",
                'description' => "VAT on " . $this->description,
                'amount' => $vatAmount,
                'business_id' => $this->biz->id,
                'type' => 'debit',
                'pre_wallet' => $this->biz->wallet - (float) $this->fee,
                'post_wallet' => $this->biz->wallet - $totalDebit,
            ]);
        }

        $this->biz->wallet -= $totalDebit;
        $this->biz->save();

        if($this->fee > 0) {
            $pnl["type"] = "income";
            $pnl["gl"] = $this->description;
            $pnl["amount"] = $this->fee;
            $pnl['status'] = 'successful';
            $pnl["narration"] = "Being amount charged for using " . $this->description . " from " . $this->biz->name . " (" . $this->biz->id . ")" . " with ref " . $this->reference;

            PndL::create($pnl);
        }

        if ((float) $this->providerCost > 0) {
            $providerPnl = [];
            $providerPnl["type"] = "expense";
            $providerPnl["gl"] = $this->providerName ? ("Provider_Cost_" . $this->providerName) : "Provider Cost";
            $providerPnl["amount"] = (float) $this->providerCost;
            $providerPnl['status'] = 'successful';
            $providerPnl["narration"] = "Being provider cost for " . $this->description . " from " . $this->biz->name . " (" . $this->biz->id . ")" . " with ref " . $this->reference;
            PndL::create($providerPnl);
        }

        if ($vatAmount > 0) {
            $vatPnl = [];
            $vatPnl["type"] = "vat";
            $vatPnl["gl"] = "VAT_Payable";
            $vatPnl["amount"] = $vatAmount;
            $vatPnl['status'] = 'successful';
            $vatPnl["narration"] = "Being VAT on " . $this->description . " from " . $this->biz->name . " (" . $this->biz->id . ")" . " with ref " . $this->reference;
            PndL::create($vatPnl);
        }
    }
}
