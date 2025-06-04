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
            "message" => "Receipt email send failed",
            "detail" => $ex->getMessage(),
            "path" => "/verification/submitted"]
         , 500);
      }

      return response([
            "timestamp" => date('c'),
            "status" => 200,
            "message" => "Receipt processed"], 200);

    }

    /**
     * Handle verification submissions that have been reviewed.
     *
     * Some reviews are prositive, others are negative. Positive reviews
     * trigger an email to the user. Negative reviews trigger an email
     * to eServices service desk to follow up with the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function completed(Request $request)
    {
      // TODO protect this endpoint.
      // TODO add logging.
      foreach(['requestStatus', 'requestResult', 'userClaims'] as $input ) {
          if(empty($request->input($input))) {
              Log::info('Required info missing');
              return response()->json([
                "timestamp" => date('c'),
                "status" => 400,
                "error" => "Bad request",
                "message" => "Required value missing: $input",
                "path" => "/verification/completed"]
             , 400);
          }
      }

      $requestResult = $request->input('requestResult');

      if($requestResult != 'APPROVED') {
        Log::warning("Result is not approved, bail here.");
        return response([
            "timestamp" => date('c'),
            "status" => 200,
            "message" => "Submission received, but not approved"], 200);
      }

      // Send the "approved" message to the user.
      $recipient = $this->getApprovedRecipient($this->request->input('userClaims'));

      try {
          Mail::to($recipient)
            ->send(new VerificationApproved());
          // Do we know if the send worked?
      } catch (\Exception $ex) {
          Log::error('Email send failed');
          return response()->json([
            "timestamp" => date('c'),
            "status" => 500,
            "error" => "Internal Server Error",
            "message" => "Approved email send failed",
            "detail" => $ex->getMessage(),
            "path" => "/verification/completed"]
         , 500);
      }

      return response([
            "timestamp" => date('c'),
            "status" => 200,
            "message" => "Receipt processed"], 200);
    }
}
