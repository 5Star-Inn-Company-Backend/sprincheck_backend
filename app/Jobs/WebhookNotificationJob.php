<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\KycLog;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class WebhookNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */

    public KycLog $klog;
    public String $number;
    public function __construct($klog, $number)
    {
        $this->klog=$klog;
        $this->number=$number;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $biz=Business::find($this->klog->business_id);

        $data=KycLog::find($this->klog->id);

        $kycDetails = null;
        if ($data && $data->type === 'BVN VERIFICATION' && $data->bvn) {
            $kycDetails = $data->bvn->data;
        } elseif ($data && $data->type === 'NIN VERIFICATION' && $data->nin) {
            $kycDetails = $data->nin->data;
        } elseif ($data && $data->type === 'DRIVERLICENSE VERIFICATION' && $data->dlicense) {
            $kycDetails = $data->dlicense->data;
        } elseif ($data && $data->type === 'PASSPORT VERIFICATION' && $data->passport) {
            $kycDetails = $data->passport->data;
        } elseif ($data && $data->type === 'VOTERS VERIFICATION' && $data->voters) {
            $kycDetails = $data->voters->data;
        } elseif ($data && ($data->type === 'FACE LIVENESS' || $data->type === 'FACE DETECTION' || $data->type === 'FACE COMPARE') && $data->facevers) {
            $kycDetails = $data->facevers->data;
        }

        $occurredAt = Carbon::now()->toIso8601String();
        $reference = $data ? $data->reference : null;

        $datas = [];
        $datas['schema_version'] = 1;
        $datas['event'] = 'verification';
        $datas['event_type'] = $data ? $data->type : null;
        $datas['event_id'] = $reference ? ($reference . ':' . $occurredAt) : (string) $occurredAt;
        $datas['occurred_at'] = $occurredAt;
        $datas['reference'] = $reference;
        $datas['number'] = $this->number;
        $datas['status'] = $data ? $data->status : null;
        $datas['source'] = $data ? $data->source : null;
        $datas['confidence'] = $data ? $data->confidence : null;
        $datas['image'] = $data ? $data->image : null;
        $datas['identifier'] = $data ? $data->identifier : null;
        $datas['kyc_details'] = json_decode($kycDetails,true);

        $data = json_encode($datas);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $biz->webhook_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => array(
                'X-Sprintcheck-Signature: '. hash_hmac("SHA512", $data, $biz->encryption_key),
                'X-Sprintcheck-Timestamp: ' . Carbon::now()->toIso8601String(),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

    }
}
