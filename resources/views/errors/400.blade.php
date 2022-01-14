<x-layout>

    <div class="alert alert-danger">
      <h2>An error occurred</h2>
      <p>Bad request: {{ $message }}</p>
    </div>
    
    <p>
        Sorry, there is a problem with this service. Try again later.
    </p>
    
    @include('shared.get_help')

</x-layout>