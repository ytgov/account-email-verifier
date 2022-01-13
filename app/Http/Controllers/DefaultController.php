<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;

class DefaultController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Show the default page.
     *
     * @param  Request  $request
     * @return Response
     */
    public function show(Request $request)
    {
        $state = $request->input('state');
        $rawSessionToken = $request->input('session_token');
        if (empty($state)) {
          // TODO Improve the response to the user here.
          Log::error('State is missing');
          abort(400, "Required information is missing.");
        }

        if (empty($rawSessionToken)) {
          // TODO Improve the response to the user here.
          Log::error('Session token is missing');
          abort(400, "Required information is missing.");
        }
        if (!$this->sessionTokenIsValid($rawSessionToken)) {
          // This is a configuration error or a security issue.
          // TODO Improve the response to the user here.
          Log::error('Session token is invalid');
          abort(400, "Unable to load user information.");
        }
        if (!$this->sessionTokenNotExpired($rawSessionToken)) {
          // User should go back to Auth0
          Log::error('Session token has expired');
          // TODO show the user an error message. Have them access
          // Auth0 again?
          abort(400, "Session has expired");
        }

        $sessionToken = $this->parseSessionToken($rawSessionToken);

        $continueUrl = $this->continueLink($state);

        return view('default', [
          'email' => $sessionToken->emailAddress,
          'continueUrl' => $continueUrl
        ]);
    }
    
    private function continueLink($state)
    {
      $idp_domain = env('IDP_DOMAIN', 'auth0.com');
      return 'https://' . $idp_domain . '/continue?state=' . $state; 
    }

    /**
     * Is the Session Token (JWT) valid?
     *
     * @param string $sessionToken The raw JWT
     * @return return bool
     */
    private function sessionTokenIsValid($sessionToken)
    {
      // FIXME Replace this placeholder.
      return true;
    }

    /**
     * Is the Session Token (JWT) still fresh?
     *
     * @param string $sessionToken The raw JWT
     * @return return bool
     */
    private function sessionTokenNotExpired($sessionToken)
    {
      // FIXME Replace this placeholder.
      return true;
    }

    /**
     * Return session information from the JWT.
     *
     * @param string $sessionToken The raw JWT
     * @return return obj
     */
    private function parseSessionToken($sessionToken)
    {
      // FIXME Replace this placeholder.
      return (object) [
        'applicationID' => '123456789',
        'userID' => 'abcefghijklmnop',
        'emailAddress' => 'franky@hollywood.com'
      ];
    }
}
