/**
* Handler that will be called during the execution of a PostLogin flow.
*
* @param {Event} event - Details about the user and the context in which they are logging in.
* @param {PostLoginAPI} api - Interface whose methods can be used to change the behavior of the login.
*/
exports.onExecutePostLogin = async (event, api) => {
    enforceEmailVerification(event, api);
};

/**
* Handler that will be invoked when this action is resuming after an external redirect. If your
* onExecutePostLogin function does not perform a redirect, this function can be safely ignored.
*
* @param {Event} event - Details about the user and the context in which they are logging in.
* @param {PostLoginAPI} api - Interface whose methods can be used to change the behavior of the login.
*/
exports.onContinuePostLogin = async (event, api) => {
    // Enforce it here too, in case users clicked on "continue to log in".
    enforceEmailVerification(event, api);
};

/**
* Handler that will be called during the execution of a PostLogin flow.
*
* Enforce email verification.
*
* This is called by both onExecutePostLogin and onContinuePostLogin.
*
* References:
* - https://auth0.com/docs/actions/triggers/post-login
* - https://auth0.com/docs/actions/triggers/post-login/event-object
* - https://auth0.com/docs/actions/triggers/post-login/api-object
* - https://community.auth0.com/t/using-the-management-api-in-flow-actions/60689
* - https://community.auth0.com/t/how-can-i-use-the-management-api-in-actions/64947
* - https://auth0.com/docs/brand-and-customize/email/manage-email-flow#verification-email
* - https://auth0.com/docs/api/management/v2#!/Jobs/post_verification_email
* - https://github.com/auth0/node-auth0/blob/master/src/management/JobsManager.js
*
* @param {Event} event - Details about the user and the context in which they are logging in.
* @param {PostLoginAPI} api - Interface whose methods can be used to change the behavior of the login.
*/
function enforceEmailVerification(event, api) {
    // Users with verified emails can pass.
    if (event.user.email_verified) {
      return;
    }
    // short-circuit if using a Refresh Token
    if (event.transaction && event.transaction.protocol === 'oauth2-refresh-token') {
      return;
    }
    // Only apply to client (applications) that require email verification.
    // TODO finish this
    if (event.client.metadata['enforce_email_verification'] != '1') {
      return;
    }

    // Redirect to the account-email-verifier application.
    // Craft a signed session token
    const token = api.redirect.encodeToken({
      secret: event.secrets.SESSION_TOKEN_SECRET,
      expiresInSeconds: 900,
      payload: {
        // Custom claims to be added to the token
        email: event.user.email,
        user_id: event.user.user_id,
        application_id: event.client.client_id,
        // aud value is the app ID of the email verification application.
        aud: event.secrets.EMAIL_VERIFICATION_APP_ID
      },
    });

    // Send the user to https://my-app.exampleco.com along
    // with a `session_token` query string param including
    // the email, user ID and application ID.
    api.redirect.sendUserTo("http://localhost:8000/start", {
      query: { session_token: token }
    });
}