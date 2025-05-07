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

        $data=KycLog::with('bvn','nin')->find($this->klog->id);

        $datas['event']="verification";
        $datas['number']=$this->number;
        $datas['data']=$data->makeHidden(['business_id','kyc_id','kycnin_id','billing_id','user_id','id','updated_at']);

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
                'payloadSignature: '. hash_hmac("SHA512", $data, $biz->encryption_key),
                'timestamp: ' . Carbon::now(),
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

    }
}
