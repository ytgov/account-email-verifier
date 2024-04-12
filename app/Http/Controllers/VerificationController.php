<?php

namespace App\Http\Controllers;

use App\Mail\VerificationReceipt;
use App\Http\Controllers;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Mail;
use Log;

class VerificationController extends BaseController
{
    /**
     * Send a receipt email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function submitted(Request $request)
    {
      // TODO protect this endpoint.
      // TODO add logging.
      $userEmail = $request->input('email');
      if (empty($userEmail)) {
          Log::info('email is missing');
          return response()->json([
            "timestamp" => date('c'),
            "status" => 400,
            "error" => "Bad request",
            "message" => "Required value missing: email",
            "path" => "/verification/submitted"]
         , 400);
      }

      $recipient = [
        [
          'email' => $userEmail,
          'name' => NULL,
        ]
      ];

      try {
          Mail::to($recipient)
            ->send(new VerificationReceipt());
          // Do we know if the send worked?
      } catch (\Exception $ex) {
          Log::error('Email send failed');
          return response()->json([
            "timestamp" => date('c'),
            "status" => 500,
            "error" => "Internal Server Error",
            "message" => "Email send failed",
            "detail" => $ex->getMessage(),
            "path" => "/verification/submitted"]
         , 500);
      }

      return response([
            "timestamp" => date('c'),
            "status" => 200,
            "message" => "Receipt processed"], 200);

    }
}
