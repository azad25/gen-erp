<x-layouts.guest :title="__('Login')">
    <div style="max-width: 480px; margin: 4rem auto; padding: 2rem;">
        <h1>{{ __('Login to GenERP') }}</h1>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <label for="email">{{ __('Email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
                @error('email') <p style="color:red;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password">{{ __('Password') }}</label>
                <input id="password" type="password" name="password" required>
                @error('password') <p style="color:red;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label>
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    {{ __('Remember me') }}
                </label>
            </div>

            <div style="margin-top: 1rem;">
                <button type="submit">{{ __('Login') }}</button>
            </div>

            <p>{{ __('Don\'t have an account?') }} <a href="{{ route('register') }}">{{ __('Register') }}</a></p>
        </form>
    </div>
</x-layouts.guest>
