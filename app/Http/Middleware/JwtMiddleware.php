<?php

namespace App\Http\Middleware;

use Closure;
use Log;
use Auth0\SDK\Token\Parser;

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
        if (empty($request->input('session_token'))) {
          Log::info('Session token is missing');
          return redirect()->route('missing_info');
        }
        $token = $this->parseSessionToken($request->input('session_token'));
        $request->session_token = $token->toArray();
        return $next($request);
    }

    /**
     * Return information from the JWT.
     *
     * Verifies the signature of the token and also it's expiry, issuer and
     * audience.
     *
     * @param string $rawJWT The raw JWT
     * @return return \Auth0\SDK\Token
     */
    private function parseSessionToken($rawJWT)
    {
      $auth0 = $this->getSdk();

      try {
        $token = new \Auth0\SDK\Token($auth0->configuration(), $rawJWT, \Auth0\SDK\Token::TYPE_ID_TOKEN);

        // Verify the token: (This will throw an \Auth0\SDK\Exception\InvalidTokenException if verification fails.)
        // Auth0 encodes tokens using HS256 in Actions.
        // See https://auth0.com/docs/customize/actions/triggers/post-login/redirect-with-actions#pass-data-to-the-external-site
        $token->verify('HS256', NULL, env('AUTH0_SESSION_TOKEN_SECRET'));
      } catch (\Auth0\SDK\Exception\InvalidTokenException $exception) {
        // The token signature is not valid.
        // This is a configuration error or a security issue.
        Log::error('Token failed to verify', ['exception' => $exception->getMessage()]);
        abort(400, 'Invalid token.');
      }
      
      // Capture expired tokens seperate of other validation errors.
      try {
        $parser = new Parser($rawJWT, $auth0->configuration());
        $validator = $parser->validate();

        $validator->expiration(60, time());

      } catch (\Auth0\SDK\Exception\InvalidTokenException $exception) {
        // if token has  expired, User should go back to Auth0?
        // TODO show an error message here with instructions to the user.
        Log::notice('Token failed expiration', ['exception' => $exception->getMessage()]);
        abort(400, 'Session expired.');
      }

      try {
        // Validate the token claims: (This will throw an \Auth0\SDK\Exception\InvalidTokenException if validation fails.)
        //
        // Need the pass in a version of the domain without the leading "https://
        // or trailing slash, otherwise, get this validation error:
        // > Issuer (iss) claim mismatch in the token; expected "https://YOUR-DOMAIM/", found "YOUR-DOMAIM"
        //
        // If you get:
        // > Audience (aud) claim must be a string or array of strings present in the token
        // Make sure the redirect token has an `aud` value.
        $token->validate(env('AUTH0_CUSTOM_DOMAIN', env('AUTH0_DOMAIN')));
        // TODO is there a way to capture expired tokens specifically?
      } catch (\Auth0\SDK\Exception\InvalidTokenException $exception) {
        // The token wasn't valid. Let's display the error message from the Auth0 SDK.
        // We'd probably want to show a custom error here for a real world application.
        // if token is not valid, this is a configuration error or a security issue.
        Log::error('Token invalid', ['exception' => $exception->getMessage()]);
        abort(400, 'Invalid token.');
      }
      
      return $token;
    }

    private function getSdk()
    {
      // TODO this should be a service or something.
      $config = [
          'strategy'     => 'webapp',
          'domain'       => env('AUTH0_DOMAIN'),
          'clientId'     => env('AUTH0_CLIENT_ID'),
          // $token->verify uses the session token secret to validate the JWT signature.
          // 'clientSecret' => env('AUTH0_CLIENT_SECRET'),
          // If you don't define audience, the SDK will use the client ID.
          // 'audience'     => ['https://validate-your-email.sign-on.service.yukon.ca/'],
      ];
      // In case we're using a custom domain, tell Auth0 about it.
      if (env('AUTH0_CUSTOM_DOMAIN')) {
        $config['customDomain'] = env('AUTH0_CUSTOM_DOMAIN');
      }
      // Now instantiate the Auth0 class with our configuration:
      $auth0 = new \Auth0\SDK\Auth0($config);

      return $auth0;
    }

}
