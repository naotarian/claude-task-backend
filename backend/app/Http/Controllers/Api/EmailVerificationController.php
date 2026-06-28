<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Verify the email. Called by the SPA /verify-email page (which forwards
     * the signed link params) as an authenticated, JSON request — so it
     * requires login and a valid signature.
     */
    public function verify(EmailVerificationRequest $request): JsonResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill();
        }

        return response()->json(['message' => 'verified']);
    }

    /**
     * Resend the verification email to the authenticated user.
     */
    public function resend(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'already verified']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'verification link sent'], 202);
    }
}
