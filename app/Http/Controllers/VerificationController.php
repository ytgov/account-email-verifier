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
     * @param  string  $userEmail
     * @return \Illuminate\Http\Response
     */
    public function submitted(Request $request)
    {
      // TODO protect this endpoint.
      // TODO add logging.
      $userEmail = $request->input('email');
      if (empty($userEmail)) {
          Log::info('email is missing');
          abort(400, "Required value missing: email");
      }

      $recipient = [
        [
          'email' => $userEmail,
          'name' => NULL,
        ]
      ];

      Mail::to($recipient)
        ->send(new VerificationReceipt());

      // TODO return 200 here to let the caller know we're done.
    }
}
