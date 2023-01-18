<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;
use Log;

class DefaultController extends BaseController
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
     * Handle the default entry path.
     *
     * TODO This checks to see if the user has had a verfication email sent already. If not
     * a verification email is set.
     *
     * Redirects to the show action.
     *
     * @param  Request  $request
     * @return Response
     */
    public function start(Request $request)
    {
        $state = $request->input('state');
        if (empty($state)) {
          Log::info('State is missing');
          return redirect()->route('missing_info');
        }

        $sessionToken = $request->session_token;

        if (empty($sessionToken['user_id'])) {
          Log::info('user_id is missing from session token.');
          return redirect()->route('missing_info');
        }

        if (empty($sessionToken['application_id'])) {
          Log::info('application_id is missing from session token.');
          return redirect()->route('missing_info');
        }

        // gold plating: check if the user is already verified.
        // Have Auth0 re-send the verification message.
        //$this->auth0ResendMessage($sessionToken['user_id'], $sessionToken['application_id']);
        $this->sendMessage($sessionToken['user_id'], $sessionToken['application_id']);

        // Redirect to the default page to show a confirmation message.
        return redirect()->route('default',
          array_merge($request->all(), ['sent' => time()])
        );
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
        if (empty($state)) {
          Log::info('State is missing');
          return redirect()->route('missing_info');
        }

        $sessionToken = $request->session_token;

        $continueUrl = $this->continueLink($state);

        return view('default', [
          'email' => $sessionToken['email'],
          'continueUrl' => $continueUrl,
          'sessionToken' => $request->input('session_token'),
          'state' => $state,
          'resent' => $request->input('resent'),
        ]);
    }

    /**
     * Resend the email address verification message.
     *
     * Then redirect back to the default page, but with a flash message.
     *
     * @param  Request  $request
     * @return Response
     */
    public function resend(Request $request)
    {
        $sessionToken = $request->session_token;
        // gold plating: check if the user is already verified.
        // Have Auth0 re-send the verification message.
        //$this->auth0ResendMessage($sessionToken['user_id'], $sessionToken['application_id']);
        $this->sendMessage($sessionToken['user_id'], $sessionToken['application_id']);

        // Redirect to the default page to show a confirmation message.
        return redirect()->route('default',
          array_merge($request->all(), ['resent' => time()])
        );
    }

    /**
     * Explain what this site is.
     *
     * This is to fail gracefully when someone who shows up without the required
     * information in the request.
     *
     * @param  Request  $request
     * @return Response
     */
    public function missing_info(Request $request)
    {
      return view('missing_info');
    }

    private function continueLink($state)
    {
      $idp_domain = env('AUTH0_DOMAIN', 'auth0.com');
      return 'https://' . $idp_domain . '/continue?state=' . $state;
    }


    /**
     * (Re)send the verification message, after getting a ticket from Auth0.
     *
     * @param string $userID The ID of the user
     * @param string $applicationID The ID of the application
     * @return TBD
     */
    private function sendMessage($userID, $applicationID)
    {
      $management = $this->getAuth0ManagementAPI();

      // TODO check if user isn't already verified.
      $response = $management->tickets()->createEmailVerification(
        $userID,
        ['client_id' => $applicationID]
      );

      // Does the status code of the response indicate failure?
      if ($response->getStatusCode() !== 201) {
        Log::critical('API request failed', ['code' => $response->getStatusCode(), 'response' => $response->getBody()]);
        abort(500, "API request failed.");
      }

      // Decode the JSON response into a PHP array:
      $ticket_response = json_decode($response->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR);
      if (empty($ticket_response['ticket'])) {
        Log::critical('Response missing ticket URL', ['code' => $response->getStatusCode(), 'response' => $response->getBody()]);
        abort(500, "API request failed.");
      }
      Log::critical('ticket URL', ['response' => $response->getBody()]);
    }

    /**
     * Have Auth0 re-send the verification message.
     *
     * @param string $userID The ID of the user
     * @param string $applicationID The ID of the application
     * @return return array The result of the API call
     */
    private function auth0ResendMessage($userID, $applicationID)
    {
      $management = $this->getAuth0ManagementAPI();
      // TODO check if user isn't already verified.

      $response = $management->jobs()->createSendVerificationEmail(
        $userID,
        ['client_id' => $applicationID]
      );

      // Does the status code of the response indicate failure?
      if ($response->getStatusCode() !== 201) {
          Log::critical('API request failed', ['code' => $response->getStatusCode(), 'response' => $response->getBody()]);
          abort(500, "API request failed.");
      }

      // Decode the JSON response into a PHP array:
      $response = json_decode($response->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR);

      // This response is the job to send the email.
      // Email sending could still fail.
      // Should we follow up o the job to monitor it's success?
      return $response;
    }

    /**
     * Get an Auth0 SDK Management API.
     *
     * @return Auth0\SDK\API\Management
     */
    private function getAuth0ManagementAPI()
    {
      $config = [
          'strategy'     => 'management',
          'domain'       => env('AUTH0_DOMAIN'),
          'clientId'     => env('AUTH0_CLIENT_ID'),
          'clientSecret' => env('AUTH0_CLIENT_SECRET'),
          // The audience for the API will always be the auth0 domain, not the
          // custom domain.
          // @see https://auth0.com/docs/customize/custom-domains/configure-features-to-use-custom-domains#apis
          'audience'     => ['https://' . env('AUTH0_DOMAIN') . 'api/v2/'],
      ];
      // In case we're using a custom domain, tell Auth0 about it.
      if (env('AUTH0_CUSTOM_DOMAIN')) {
        $config['customDomain'] = env('AUTH0_CUSTOM_DOMAIN');
      }
      $auth0 = new \Auth0\SDK\Auth0($config);

      // Create a configured instance of the `Auth0\SDK\API\Management` class, based on the configuration we setup the SDK ($auth0) using.
      // This will automatically perform a client credentials exchange to generate one for you, so long as a client secret is configured.
      return $auth0->management();
    }
}
