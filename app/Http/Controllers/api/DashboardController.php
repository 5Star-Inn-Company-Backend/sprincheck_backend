<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\KycLog;
use App\Models\VirtualAccount;
use App\Models\WalletTracker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function getDashboard(Request $request)
    {
        $user = $request->user();
        $business = $user->business;

        // Get wallet balance
        $walletBalance = $business->wallet ?? 0;

        // Get virtual accounts
        $virtualAccounts = VirtualAccount::where([['business_id', $business->id], ['status',1]])->select('id','account_number','customer_name','bank_name')->get();

        // Get API call statistics
        $totalCalls = KycLog::where('business_id', $business->id)->count();
        $successfulCalls = KycLog::where('business_id', $business->id)
            ->where('status', 1)
            ->count();
        $failedCalls = KycLog::where('business_id', $business->id)
            ->where('status', 0)
            ->count();


        return response()->json([
            'status' => true,
            'data' => [
                'user' => $user,
                'wallet_balance' => $walletBalance,
                'virtual_accounts' => $virtualAccounts,
                'api_calls' => [
                    'total' => $totalCalls,
                    'successful' => $successfulCalls,
                    'failed' => $failedCalls
                ],
            ]
        ]);
    }

    public function getDashboardStats(Request $request)
    {
        $user = $request->user();
        $business = $user->business;

        // Get daily stats for the last 8 days
        $dailyStats = KycLog::where('business_id', $business->id)
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as successful'),
                DB::raw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as failed')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // Format the response
        $graphData = [];
        foreach ($dailyStats as $stat) {
            $graphData[] = [
                'date' => $stat->date,
                'successful' => (int)$stat->successful,
                'failed' => (int)$stat->failed
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $graphData
        ]);
    }

    
    public function getHistory(Request $request)
    {
        $user = $request->user();
        $business = $user->business;

        $data = KycLog::with('bvn','nin')->where('business_id', $business->id)->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    
    public function getWalletHistory(Request $request)
    {
        $user = $request->user();
        $business = $user->business;

        $data = WalletTracker::where('business_id', $business->id)->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function updateWebhookUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => implode(', ', $validator->errors()->all()),
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $business = $user->business;

        $business->update([
            'webhook_url' => $request->webhook_url
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Webhook URL updated successfully'
        ]);
    }
}
