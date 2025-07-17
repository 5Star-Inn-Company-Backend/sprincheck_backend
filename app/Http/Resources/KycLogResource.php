<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KycLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $log=$this;
        $kyc_info = null;
        if ($log->type === 'BVN VERIFICATION') {
            $kyc_info = $log->bvn->data;
        } elseif ($log->type === 'NIN VERIFICATION') {
            $kyc_info = $log->nin->data;
        }

        return [
            'id' => $log->id,
            'business_id' => $log->business_id,
            'type' => $log->type,
            'identifier' => $log->identifier,
            'user_id' => $log->user_id,
            'source' => $log->source,
            'confidence' => $log->confidence,
            'image' => $log->image,
            'status' => $log->status,
            'created_at' => $log->created_at,
            'kyc_details' => $kyc_info,
        ];
    }
}
