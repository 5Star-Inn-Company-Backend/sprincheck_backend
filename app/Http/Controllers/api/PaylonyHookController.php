<?php

namespace App\Http\Controllers\api;


use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\PndL;
use App\Models\User;
use App\Models\VirtualAccountClient;
use App\Models\WalletTracker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaylonyHookController extends Controller
{

    public function index(Request $request)
    {
        $input = $request->all();

        $data2 = json_encode($input);

        try {
            DB::table('tbl_webhook_paylony')->insert(['payment_reference' => $input['reference'], 'payment_id' => $input['trx'], 'status' => $input['status'], 'amount' => $input['amount'], 'fees' => $input['fee'], 'receiving_account' => $input['receiving_account'], 'paid_at' => Carbon::now(), 'channel' => $input['channel'], 'remote_address' => $_SERVER['REMOTE_ADDR'], 'extra' => $data2]);
        } catch (\Exception $e) {
            Log::info("Paylony crashed. - " . $data2);
        }

        // find account number match
        $vac = VirtualAccountClient::where('account_number', $input['receiving_account'])->latest()->first();

        if ($vac) {

            $rvn['amount'] = $input['amount'];
            $rvn['provider_charges'] = $input['fee'];
            $rvn['from_acct_name'] = $input['sender_account_name'];
            $rvn['sender_bank'] = $input['sender_bank_code'];
            $rvn['from_acct_number'] = $input['sender_account_number'];
            $rvn['narration'] = $input['sender_narration'];
            $rvn['receiver_bank'] = $input['gateway'];
            $rvn['acct_number'] = $input['receiving_account'];
            $rvn['transactionreference'] = $input['trx'];
            $rvn['date'] = $input["timestamp"];
            $rvn['server'] = "Paylony";
            $gateway = $input['gateway'];
            $rvn['charges'] = 80;
            $rvn['business_id'] = $vac->business_id;
            $rvn['amount_credit'] = $rvn['amount'] - $rvn['charges'];

            if ($rvn['amount_credit'] > 0) {
                $w = WalletTracker::where('reference', $rvn['transactionreference'])->first();
                if (!$w) {
                    $this->fundBusinessWallet($rvn);
                }
            }

            return "success";
        }

        return "success but acct not found";
    }


    private function fundBusinessWallet($rvn)
    {
        $biz = Business::find($rvn['business_id']);

        WalletTracker::create([
            'reference' => $rvn['transactionreference'],
            'description' => 'Business Account Funded by ' . $rvn['from_acct_name'],
            'amount' => $rvn['amount'],
            'business_id' => $biz->id,
            'type' => 'credit',
            'pre_wallet' => $biz->wallet,
            'post_wallet' => $biz->wallet + $rvn['amount'],
        ]);

        WalletTracker::create([
            'reference' => $rvn['transactionreference']."_fee",
            'description' => "Fee charges for funding",
            'amount' => $rvn['charges'],
            'business_id' => $biz->id,
            'type' => 'debit',
            'pre_wallet' => $biz->wallet + $rvn['amount'],
            'post_wallet' => $biz->wallet + $rvn['amount'] - $rvn['charges'],
        ]);


        $biz->wallet += $rvn['amount_credit'];
        $biz->save();

        $pnl["type"] = "income";
        $pnl["gl"] = "Automated Funding";
        $pnl["amount"] = $rvn['charges'];
        $pnl['status'] = 'successful';
        $pnl["narration"] = "Being amount charged for using automated funding from " . $biz->name . " (" . $biz->id . ")" . " with ref " . $rvn['transactionreference'];

        PndL::create($pnl);
    }
}

//{
//"status": "00",
//"currency": "NGN",
//"amount": "5000",
//“fee”: “50”,
//"receiving_account": "1010011220”,
//    "sender_account_name": "Tolulope Oyeniyi",
//    "sender_account_number": "00012302099",
//    "sender_bank_code": "999999",
//    "sender_narration": "Gift bills deposit",
//    "sessionId": "0000929120221226145000",
//    "trx": "202212261450837964477",
//    "reference": "Vfd-x-20221226145000",
//    "channel": "bank_transfer",
//    "type": "reserved_account",
//    "domain": "test",
//    "gateway": "vfd"
//}

