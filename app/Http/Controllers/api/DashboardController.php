<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KycLogResource;
use App\Models\Business;
use App\Models\KycLog;
use App\Models\TransactionFee;
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
     * Get list of all countries (ISO 3166-1 alpha-2)
     */
    public function getCountries()
    {
        $countries = [
            ['code' => 'AF', 'name' => 'Afghanistan'],
            ['code' => 'AL', 'name' => 'Albania'],
            ['code' => 'DZ', 'name' => 'Algeria'],
            ['code' => 'AD', 'name' => 'Andorra'],
            ['code' => 'AO', 'name' => 'Angola'],
            ['code' => 'AG', 'name' => 'Antigua & Barbuda'],
            ['code' => 'AR', 'name' => 'Argentina'],
            ['code' => 'AM', 'name' => 'Armenia'],
            ['code' => 'AU', 'name' => 'Australia'],
            ['code' => 'AT', 'name' => 'Austria'],
            ['code' => 'AZ', 'name' => 'Azerbaijan'],
            ['code' => 'BS', 'name' => 'Bahamas'],
            ['code' => 'BH', 'name' => 'Bahrain'],
            ['code' => 'BD', 'name' => 'Bangladesh'],
            ['code' => 'BB', 'name' => 'Barbados'],
            ['code' => 'BY', 'name' => 'Belarus'],
            ['code' => 'BE', 'name' => 'Belgium'],
            ['code' => 'BZ', 'name' => 'Belize'],
            ['code' => 'BJ', 'name' => 'Benin'],
            ['code' => 'BT', 'name' => 'Bhutan'],
            ['code' => 'BO', 'name' => 'Bolivia'],
            ['code' => 'BA', 'name' => 'Bosnia & Herzegovina'],
            ['code' => 'BW', 'name' => 'Botswana'],
            ['code' => 'BR', 'name' => 'Brazil'],
            ['code' => 'BN', 'name' => 'Brunei'],
            ['code' => 'BG', 'name' => 'Bulgaria'],
            ['code' => 'BF', 'name' => 'Burkina Faso'],
            ['code' => 'BI', 'name' => 'Burundi'],
            ['code' => 'CV', 'name' => 'Cabo Verde'],
            ['code' => 'KH', 'name' => 'Cambodia'],
            ['code' => 'CM', 'name' => 'Cameroon'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'CF', 'name' => 'Central African Republic'],
            ['code' => 'TD', 'name' => 'Chad'],
            ['code' => 'CL', 'name' => 'Chile'],
            ['code' => 'CN', 'name' => 'China'],
            ['code' => 'CO', 'name' => 'Colombia'],
            ['code' => 'KM', 'name' => 'Comoros'],
            ['code' => 'CG', 'name' => 'Congo'],
            ['code' => 'CR', 'name' => 'Costa Rica'],
            ['code' => 'CI', 'name' => "Côte d'Ivoire"],
            ['code' => 'HR', 'name' => 'Croatia'],
            ['code' => 'CU', 'name' => 'Cuba'],
            ['code' => 'CY', 'name' => 'Cyprus'],
            ['code' => 'CZ', 'name' => 'Czech Republic'],
            ['code' => 'DK', 'name' => 'Denmark'],
            ['code' => 'DJ', 'name' => 'Djibouti'],
            ['code' => 'DM', 'name' => 'Dominica'],
            ['code' => 'DO', 'name' => 'Dominican Republic'],
            ['code' => 'CD', 'name' => 'DR Congo'],
            ['code' => 'EC', 'name' => 'Ecuador'],
            ['code' => 'EG', 'name' => 'Egypt'],
            ['code' => 'SV', 'name' => 'El Salvador'],
            ['code' => 'GQ', 'name' => 'Equatorial Guinea'],
            ['code' => 'ER', 'name' => 'Eritrea'],
            ['code' => 'EE', 'name' => 'Estonia'],
            ['code' => 'SZ', 'name' => 'Eswatini'],
            ['code' => 'ET', 'name' => 'Ethiopia'],
            ['code' => 'FJ', 'name' => 'Fiji'],
            ['code' => 'FI', 'name' => 'Finland'],
            ['code' => 'FR', 'name' => 'France'],
            ['code' => 'GA', 'name' => 'Gabon'],
            ['code' => 'GM', 'name' => 'Gambia'],
            ['code' => 'GE', 'name' => 'Georgia'],
            ['code' => 'DE', 'name' => 'Germany'],
            ['code' => 'GH', 'name' => 'Ghana'],
            ['code' => 'GR', 'name' => 'Greece'],
            ['code' => 'GD', 'name' => 'Grenada'],
            ['code' => 'GT', 'name' => 'Guatemala'],
            ['code' => 'GN', 'name' => 'Guinea'],
            ['code' => 'GW', 'name' => 'Guinea-Bissau'],
            ['code' => 'GY', 'name' => 'Guyana'],
            ['code' => 'HT', 'name' => 'Haiti'],
            ['code' => 'VA', 'name' => 'Holy See'],
            ['code' => 'HN', 'name' => 'Honduras'],
            ['code' => 'HU', 'name' => 'Hungary'],
            ['code' => 'IS', 'name' => 'Iceland'],
            ['code' => 'IN', 'name' => 'India'],
            ['code' => 'ID', 'name' => 'Indonesia'],
            ['code' => 'IR', 'name' => 'Iran'],
            ['code' => 'IQ', 'name' => 'Iraq'],
            ['code' => 'IE', 'name' => 'Ireland'],
            ['code' => 'IL', 'name' => 'Israel'],
            ['code' => 'IT', 'name' => 'Italy'],
            ['code' => 'JM', 'name' => 'Jamaica'],
            ['code' => 'JP', 'name' => 'Japan'],
            ['code' => 'JO', 'name' => 'Jordan'],
            ['code' => 'KZ', 'name' => 'Kazakhstan'],
            ['code' => 'KE', 'name' => 'Kenya'],
            ['code' => 'KI', 'name' => 'Kiribati'],
            ['code' => 'KW', 'name' => 'Kuwait'],
            ['code' => 'KG', 'name' => 'Kyrgyzstan'],
            ['code' => 'LA', 'name' => 'Laos'],
            ['code' => 'LV', 'name' => 'Latvia'],
            ['code' => 'LB', 'name' => 'Lebanon'],
            ['code' => 'LS', 'name' => 'Lesotho'],
            ['code' => 'LR', 'name' => 'Liberia'],
            ['code' => 'LY', 'name' => 'Libya'],
            ['code' => 'LI', 'name' => 'Liechtenstein'],
            ['code' => 'LT', 'name' => 'Lithuania'],
            ['code' => 'LU', 'name' => 'Luxembourg'],
            ['code' => 'MG', 'name' => 'Madagascar'],
            ['code' => 'MW', 'name' => 'Malawi'],
            ['code' => 'MY', 'name' => 'Malaysia'],
            ['code' => 'MV', 'name' => 'Maldives'],
            ['code' => 'ML', 'name' => 'Mali'],
            ['code' => 'MT', 'name' => 'Malta'],
            ['code' => 'MH', 'name' => 'Marshall Islands'],
            ['code' => 'MR', 'name' => 'Mauritania'],
            ['code' => 'MU', 'name' => 'Mauritius'],
            ['code' => 'MX', 'name' => 'Mexico'],
            ['code' => 'FM', 'name' => 'Micronesia'],
            ['code' => 'MD', 'name' => 'Moldova'],
            ['code' => 'MC', 'name' => 'Monaco'],
            ['code' => 'MN', 'name' => 'Mongolia'],
            ['code' => 'ME', 'name' => 'Montenegro'],
            ['code' => 'MA', 'name' => 'Morocco'],
            ['code' => 'MZ', 'name' => 'Mozambique'],
            ['code' => 'MM', 'name' => 'Myanmar'],
            ['code' => 'NA', 'name' => 'Namibia'],
            ['code' => 'NR', 'name' => 'Nauru'],
            ['code' => 'NP', 'name' => 'Nepal'],
            ['code' => 'NL', 'name' => 'Netherlands'],
            ['code' => 'NZ', 'name' => 'New Zealand'],
            ['code' => 'NI', 'name' => 'Nicaragua'],
            ['code' => 'NE', 'name' => 'Niger'],
            ['code' => 'NG', 'name' => 'Nigeria'],
            ['code' => 'KP', 'name' => 'North Korea'],
            ['code' => 'MK', 'name' => 'North Macedonia'],
            ['code' => 'NO', 'name' => 'Norway'],
            ['code' => 'OM', 'name' => 'Oman'],
            ['code' => 'PK', 'name' => 'Pakistan'],
            ['code' => 'PW', 'name' => 'Palau'],
            ['code' => 'PA', 'name' => 'Panama'],
            ['code' => 'PG', 'name' => 'Papua New Guinea'],
            ['code' => 'PY', 'name' => 'Paraguay'],
            ['code' => 'PE', 'name' => 'Peru'],
            ['code' => 'PH', 'name' => 'Philippines'],
            ['code' => 'PL', 'name' => 'Poland'],
            ['code' => 'PT', 'name' => 'Portugal'],
            ['code' => 'QA', 'name' => 'Qatar'],
            ['code' => 'RO', 'name' => 'Romania'],
            ['code' => 'RU', 'name' => 'Russia'],
            ['code' => 'RW', 'name' => 'Rwanda'],
            ['code' => 'KN', 'name' => 'Saint Kitts & Nevis'],
            ['code' => 'LC', 'name' => 'Saint Lucia'],
            ['code' => 'WS', 'name' => 'Samoa'],
            ['code' => 'SM', 'name' => 'San Marino'],
            ['code' => 'ST', 'name' => 'Sao Tome & Principe'],
            ['code' => 'SA', 'name' => 'Saudi Arabia'],
            ['code' => 'SN', 'name' => 'Senegal'],
            ['code' => 'RS', 'name' => 'Serbia'],
            ['code' => 'SC', 'name' => 'Seychelles'],
            ['code' => 'SL', 'name' => 'Sierra Leone'],
            ['code' => 'SG', 'name' => 'Singapore'],
            ['code' => 'SK', 'name' => 'Slovakia'],
            ['code' => 'SI', 'name' => 'Slovenia'],
            ['code' => 'SB', 'name' => 'Solomon Islands'],
            ['code' => 'SO', 'name' => 'Somalia'],
            ['code' => 'ZA', 'name' => 'South Africa'],
            ['code' => 'KR', 'name' => 'South Korea'],
            ['code' => 'SS', 'name' => 'South Sudan'],
            ['code' => 'ES', 'name' => 'Spain'],
            ['code' => 'LK', 'name' => 'Sri Lanka'],
            ['code' => 'VC', 'name' => 'St. Vincent & Grenadines'],
            ['code' => 'PS', 'name' => 'State of Palestine'],
            ['code' => 'SD', 'name' => 'Sudan'],
            ['code' => 'SR', 'name' => 'Suriname'],
            ['code' => 'SE', 'name' => 'Sweden'],
            ['code' => 'CH', 'name' => 'Switzerland'],
            ['code' => 'SY', 'name' => 'Syria'],
            ['code' => 'TJ', 'name' => 'Tajikistan'],
            ['code' => 'TZ', 'name' => 'Tanzania'],
            ['code' => 'TH', 'name' => 'Thailand'],
            ['code' => 'TL', 'name' => 'Timor-Leste'],
            ['code' => 'TG', 'name' => 'Togo'],
            ['code' => 'TO', 'name' => 'Tonga'],
            ['code' => 'TT', 'name' => 'Trinidad & Tobago'],
            ['code' => 'TN', 'name' => 'Tunisia'],
            ['code' => 'TR', 'name' => 'Turkey'],
            ['code' => 'TM', 'name' => 'Turkmenistan'],
            ['code' => 'TV', 'name' => 'Tuvalu'],
            ['code' => 'UG', 'name' => 'Uganda'],
            ['code' => 'UA', 'name' => 'Ukraine'],
            ['code' => 'AE', 'name' => 'United Arab Emirates'],
            ['code' => 'GB', 'name' => 'United Kingdom'],
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'UY', 'name' => 'Uruguay'],
            ['code' => 'UZ', 'name' => 'Uzbekistan'],
            ['code' => 'VU', 'name' => 'Vanuatu'],
            ['code' => 'VE', 'name' => 'Venezuela'],
            ['code' => 'VN', 'name' => 'Vietnam'],
            ['code' => 'YE', 'name' => 'Yemen'],
            ['code' => 'ZM', 'name' => 'Zambia'],
            ['code' => 'ZW', 'name' => 'Zimbabwe']
        ];
        return response()->json([
            'status' => true,
            'data' => $countries
        ]);
    }

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

        $data = KycLog::where('business_id', $business->id)->orderBy('created_at', 'desc')->paginate(20);

        $transformedData = $data->through(function ($log) {
//            return KycLogResource::collection($log);
            return new KycLogResource($log);
        });

        return response()->json([
            'status' => true,
            'data' => $transformedData->toArray()
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

    public function pricing(Request $request)
    {
        $user = $request->user();
        $business = $user->business;

        $trans_fees=TransactionFee::where(["business_id" => 0, 'status' =>1])->get();

        // Format the response
        $graphData = [];
        foreach ($trans_fees as $stat) {
            $bizf=TransactionFee::where(["business_id" => $business->id, 'service' => $stat->service, 'status' =>1])->first();
            if($bizf){
                $graphData[] = $bizf;
            }else{
                $graphData[] = $stat;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Pricing Fetched successfully',
            'data' => $graphData
        ]);
    }
}
