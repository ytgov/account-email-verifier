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

        $token = $this->parseSessionToken($rawSessionToken);

        $sessionToken = $token->toArray();

        $continueUrl = $this->continueLink($state);

        return view('default', [
          'email' => $sessionToken['email'],
          'continueUrl' => $continueUrl,
          'sessionToken' => $rawSessionToken,
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
        $state = $request->input('state');
        $rawSessionToken = $request->input('session_token');

        // Validate token here.
        $token = $this->parseSessionToken($rawSessionToken);
        $sessionToken = $token->toArray();
        // gold plating: check if the user is already verified.
        // Have Auth0 re-send the verification message.
        $this->auth0ResendMessage($sessionToken['user_id'], $sessionToken['application_id']);

        // Redirect to the default page to show a confirmation message.
        return redirect()->route('default', [
          'state' => $state,
          'session_token' => $rawSessionToken,
          'resent' => time(),
        ]);
    }

    private function continueLink($state)
    {
      $idp_domain = env('AUTH0_DOMAIN', 'auth0.com');
      return $idp_domain . '/continue?state=' . $state; 
    }

    /**
     * Return session information from the JWT.
     *
     * @param string $sessionToken The raw JWT
     * @return return \Auth0\SDK\Token
     */
    private function parseSessionToken($rawJWT)
    {
      $auth0 = $this->getSdk();
      $token = new \Auth0\SDK\Token($auth0->configuration(), $rawJWT, \Auth0\SDK\Token::TYPE_ID_TOKEN);

      try {
        // Verify the token: (This will throw an \Auth0\SDK\Exception\InvalidTokenException if verification fails.)
        $token->verify('HS256', NULL, env('AUTH0_SESSION_TOKEN_SECRET'));

        // Validate the token claims: (This will throw an \Auth0\SDK\Exception\InvalidTokenException if validation fails.)
        $token->validate();
      } catch (\Auth0\SDK\Exception\InvalidTokenException $exception) {
        // The token wasn't valid. Let's display the error message from the Auth0 SDK.
        // We'd probably want to show a custom error here for a real world application.
        // if token is not valid, this is a configuration error or a security issue.
        // if token has  expired, User should go back to Auth0?
        Log::error('Token invalid', ['token' => $token, 'exception' => $exception->getMessage()]);
        // TODO Fail better here.
        abort(400, $exception->getMessage());
      }
      
      return $token;
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
     *
     * @param string $userID The ID of the user
     * @param string $applicationID The ID of the application
     * @return return array The result of the API call
     */
    private function auth0ResendMessage($userID, $applicationID)
    {
      $auth0 = new \Auth0\SDK\Auth0([
          'strategy'     => 'management',
          'domain'       => env('AUTH0_DOMAIN'),
          'clientId'     => env('AUTH0_CLIENT_ID'),
          'clientSecret' => env('AUTH0_CLIENT_SECRET'),
          'audience'     => [env('AUTH0_DOMAIN') . 'api/v2/'],
      ]);

      // Create a configured instance of the `Auth0\SDK\API\Management` class, based on the configuration we setup the SDK ($auth0) using.
      // This will automatically perform a client credentials exchange to generate one for you, so long as a client secret is configured.
      $management = $auth0->management();
      
      // TODO check if user isn't already verified.
      
      $response = $management->jobs()->createSendVerificationEmail(
        $userID,
        ['client_id' => $applicationID]
      );
      
      // Does the status code of the response indicate failure?
      if ($response->getStatusCode() !== 201) {
          Log::error('API request failed', ['code' => $response->getStatusCode(), 'response' => $response->getBody()]);
          abort(500, "API request failed.");
      }

      // Decode the JSON response into a PHP array:
      $response = json_decode($response->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR);

      // This response is the job to send the email.
      // Email sending could still fail.
      // Should we follow up o the job to monitor it's success?
      return $response;
    }
}
