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
use Illuminate\Support\Str;

class DashboardController extends Controller
{

    /**
     * Update authenticated user's business info
     */
    public function updateBusinessInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_email' => 'required|email|max:255',
            'business_phone_number' => 'required|string|max:20',
            'business_registration_number' => 'nullable|string|max:100',
            'business_address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'business_description' => 'nullable|string|max:1000',
            'country' => 'nullable|string|max:100',
            'tax_identification_number' => 'nullable|string|max:100',
            'business_website' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => implode(', ', $validator->errors()->all()),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $business = $user->business;
        $business->update($request->only([
            'business_email',
            'business_phone_number',
            'business_registration_number',
            'business_address',
            'city',
            'business_description',
            'country',
            'tax_identification_number',
            'business_website',
        ]));

        return response()->json([
            'status' => true,
            'message' => 'Business info updated successfully',
            'data' => $business
        ]);
    }


    /**
     * Update authenticated user's profile (name and phone_number only)
     */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => implode(', ', $validator->errors()->all()),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->update($request->only(['name', 'phone_number']));

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->only(['id', 'name', 'email', 'phone_number'])
        ]);
    }

    /**
     * Change password for authenticated user
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => implode(', ', $validator->errors()->all()),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        if (!\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        $user->password = \Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    public function getDashboard(Request $request)
    {
        $user = $request->user();
        $business = $user->business;

        // Get wallet balance
        $walletBalance = $business->wallet ?? 0;

        // Get virtual accounts
        $virtualAccounts = VirtualAccount::where([['business_id', $business->id], ['status','active']])->select('id','account_number','customer_name','bank_name')->get();

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

    public function regenerateKeys(Request $request)
    {
        $user = $request->user();
        $business = $user->business;

        $business->update([
            'encryption_key' => 'enc'.Str::random(29),
            'api_key' => "scb".Str::random(42),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Business Keys regenerated successfully',
            'data' => $business
        ]);
    }
}
