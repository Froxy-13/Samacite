<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\BaseController;
use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FcmTokenController extends BaseController
{
    /**
     * Store or update FCM token for the authenticated user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|max:255',
            'device_type' => 'required|in:android,ios,web',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $fcmToken = FcmToken::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'token' => $request->token,
                ],
                [
                    'device_type' => $request->device_type,
                    'last_used_at' => now(),
                ]
            );

            return $this->success(
                [
                    'id' => $fcmToken->id,
                    'token' => $fcmToken->token,
                    'device_type' => $fcmToken->device_type,
                ],
                'FCM token registered successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Server Error', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete FCM token for the authenticated user
     */
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $deleted = FcmToken::where('user_id', auth()->id())
                ->where('token', $request->token)
                ->delete();

            if (!$deleted) {
                return $this->error('Token not found', 404);
            }

            return $this->success([], 'FCM token deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Server Error', 500, ['error' => $e->getMessage()]);
        }
    }
}
