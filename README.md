# account-email-verifier

Email verification step during setup of Government of Yukon online services account.

Built using the Lumen PHP Framework and the [Auth0 PHP SDK](https://github.com/auth0/Auth0-PHP). Documentation for the framework can be found on the [Lumen website](https://lumen.laravel.com/docs).

## High level overview

1. A user creates an account, or logs in at Auth0.
2. An Action or Rule in Auth0 redirects the user who does not have a verified email address to this application.
3. This application get a verification ticket from Auth0 and send a verification message.
4. This application explains the verification requirement and allows the user to optionally send another verification message.
5. Once email address is verified, the user can resume their log in process.

## Session payload

This application expects to receive user information from a JWT passed as the `session_token` argument in the request URL.

Inside the `session_token`, the following values are expected:

- `email` - used to display to the user so they know where the message was sent.
- `user_id` - used with the Auth0 management API to resend the message.
- `application_id` - so the verification message can send the user through to the right place after verification.

## Auth0 setup

### Redirect to this application

Either a Rule or an Action is required to redirect users who have not verified their email address to this application.

An sample Post-Login Action is included, see `postlogin-action-enforce-email-verification.js`.

The `session_token` value is signed with a shared secret. Both Auth0 and this application need to know the secret.

### Setup Account email verifier as an Auth0 application

The application needs the `create:user_tickets` `create:user_tickets` scopes and access to the management API.

Used https://auth0.com/docs/api/management/v2#!/Client_Grants/post_client_grants to create a grant for `create:user_tickets` with the audience https://YOUR-DOMAIN/api/v2/

e.g.
```
{
  "client_id": "FtB...MfBymF",
  "audience": "https://dev-0123abc.eu.auth0.com/api/v2/",
  "scope": [
    "create:user_tickets"
  ]
}
```

The `client_id` here is the Account email verifier application client ID, not the API client ID.

## Configuration

- Copy `.env.example` to `.env`
- Edit your `.env`.

## Requirements

- PHP 8
- Composer
- [Lumen requirements](https://lumen.laravel.com/docs/8.x/installation#server-requirements)
  - OpenSSL PHP Extension
  - Mbstring PHP Extension
