<x-layout>

                <div class="alert alert-info">
                  <p>Your email address needs to be verified before you can continue.</p>
                </div>


        <h2>Check your email</h2>
        
        @if ($resent)
          
          <div class="alert alert-success">
            <p>We’ve sent you a new message to verify your email address.</p>
          </div>
          
        @endif

        <p>We have sent an email message to <code>{{ $email }}</code>.</p>

        <p>Choose the <em>Verify my email</em> button in that message. </p>

        <p>After you verify, you can close this browser window.</p>

        <hr>

      @if (!$resent)

        <h4>Didn't get an email?</h4>

        <p>It can sometimes take a few minutes for a message to arrive. Check your Spam or Junk folder if you can't find it. </p>
        
        <p>If you lost your verification email or want to request a new email, we can send another message.</p>

        <form class="" action="/" method="post">
          <input type="hidden" name="state" value="{{ $state }}">
          <input type="hidden" name="session_token" value="{{ $sessionToken }}">
          <input type="submit" name="submit" class="btn btn-default" value="Send a new verification email">
        </form>

      @endif

      @include('shared.get_help')

</x-layout>