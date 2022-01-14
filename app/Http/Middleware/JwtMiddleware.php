<?php

namespace App\Http\Middleware;

use Closure;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * This function parses the JWT in the session_token input. It populates a
     * `session_token` value (array) on the request object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $token = $this->parseSessionToken($request->input('session_token'));
        $request->session_token = $token->toArray();
        return $next($request);
    }

    /**
     * Return information from the JWT.
     *
     * @param string $rawJWT The raw JWT
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

}
