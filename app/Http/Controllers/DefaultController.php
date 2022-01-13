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
          'email' => $sessionToken->email,
          'continueUrl' => $continueUrl,
          'sessionToken' => $rawSessionToken,
          'state' => $state,
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
        $state = $request->input('state');
        $rawSessionToken = $request->input('session_token');

        // validate token here. This one is in POST).
        $sessionToken = $this->parseSessionToken($rawSessionToken);
        // gold plating: check if the user is already verified.
        // Have Auth0 re-send the verification message.
        $this->auth0ResendMessage($sessionToken->user_id, $sessionToken->application_id);
    }

    private function continueLink($state)
    {
      $idp_domain = env('AUTH0_DOMAIN', 'auth0.com');
      return $idp_domain . '/continue?state=' . $state; 
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
    private function parseSessionToken($rawJWT)
    {
      $key = env('AUTH0_SESSION_TOKEN_SECRET', NULL);
      
      // From https://auth0.com/docs/quickstart/backend/php/
      // Trim whitespace from token string.
      $jwt = trim($rawJWT);

      // Attempt to decode the token:
      try {
          $token = $this->getSdk()->decode($jwt, null, null, null, null, null, null, \Auth0\SDK\Token::TYPE_TOKEN);
          define('ENDPOINT_AUTHORIZED', true);
          return (object) $token->toArray();
      } catch (\Auth0\SDK\Exception\InvalidTokenException $exception) {
          // The token wasn't valid. Let's display the error message from the Auth0 SDK.
          // We'd probably want to show a custom error here for a real world application.
          // TODO Fail better here.
          abort(400, $exception->getMessage());
      }
    }
    
    private function getSdk()
    {
      // TODO this should be a service or something.
      // Now instantiate the Auth0 class with our configuration:
      $auth0 = new \Auth0\SDK\Auth0([
          'strategy'     => 'webapp',
          'domain'       => env('AUTH0_DOMAIN'),
          'clientId'     => env('AUTH0_CLIENT_ID'),
          // decode() uses client secret to validate the JWT signature.
          'clientSecret' => env('AUTH0_SESSION_TOKEN_SECRET'),
          // Not clear on what the real world audience will be.
          'audience'     => ['test', 'https://validate-your-email.sign-on.service.yukon.ca/'],
          // Auth0 encodes tokens using HS256 in Actions.
          // See https://auth0.com/docs/customize/actions/triggers/post-login/redirect-with-actions#pass-data-to-the-external-site
          'tokenAlgorithm' => 'HS256',
      ]);
      return $auth0;
    }
    
    /**
     * Have Auth0 re-send the verification message.
     */
    private function auth0ResendMessage($userID, $applicationID)
    {
      // $auth0 = $this->getSdk();
      $auth0 = new \Auth0\SDK\Auth0([
          'strategy'     => 'management',
          'domain'       => env('AUTH0_DOMAIN'),
          'clientId'     => env('AUTH0_CLIENT_ID'),
          'clientSecret' => env('AUTH0_CLIENT_SECRET'),
          'audience'     => ['https://dev-0tc6bn14.eu.auth0.com/api/v2/'],
      ]);

      // Create a configured instance of the `Auth0\SDK\API\Management` class, based on the configuration we setup the SDK ($auth0) using.
      // If no AUTH0_MANAGEMENT_API_TOKEN is configured, this will automatically perform a client credentials exchange to generate one for you, so long as a client secret is configured.
      $management = $auth0->management();
      
      // TODO check if user isn't already verified.
      
      $response = $management->jobs()->createSendVerificationEmail($userID, ['client_id' => $applicationID]);
      
      // Does the status code of the response indicate failure?
      if ($response->getStatusCode() !== 201) {
          Log::error('API request failed', ['code' => $response->getStatusCode(), 'response' => $response->getBody()]);
          abort(500, "API request failed.");
      }

      // Decode the JSON response into a PHP array:
      $response = json_decode($response->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR);

      if (! empty($response)) {
          // FIXME replace this with a redirect and confirmation message.
          echo '<h2>createSendVerificationEmail response</h2>';

          print_r($response);
      }
    }
}
