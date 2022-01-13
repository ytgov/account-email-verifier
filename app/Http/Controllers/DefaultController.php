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
        $session_token = $request->input('session_token');
        if (empty($state)) {
          // TODO Improve the response to the user here.
          Log::error('State is missing');
          abort(500, "Required information is missing.");
        }

        if (empty($session_token)) {
          // TODO Improve the response to the user here.
          Log::error('Session token is missing');          
          abort(500, "Required information is missing.");
        }

        $continueUrl = $this->continueLink($state);
        return view('default', [
          'email' => 'tester@gmail.com',
          'continueUrl' => $continueUrl
        ]);
    }
    
    private function continueLink($state)
    {
      $idp_domain = env('IDP_DOMAIN', 'auth0.com');
      return 'https://' . $idp_domain . '/continue?state=' . $state; 
    }
}
