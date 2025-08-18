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
        } elseif ($log->type === 'DRIVERLICENSE VERIFICATION') {
            $kyc_info = $log->dlicense->data;
        } elseif ($log->type === 'PASSPORT VERIFICATION') {
            $kyc_info = $log->passport->data;
        } elseif ($log->type === 'VOTERS VERIFICATION') {
            $kyc_info = $log->voters->data;
        } elseif ($log->type === 'FACE LIVENESS' || $log->type === 'FACE DETECTION' || $log->type === 'FACE COMPARE') {
            $kyc_info = $log->facevers->data;
//        } elseif ($log->type === 'FACIAL VERIFICATION') {
//            $kyc_info = $log->facial->source_image;
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
