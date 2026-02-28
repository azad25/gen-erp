<x-layouts.guest :title="__('Register')">
    <div style="max-width: 480px; margin: 4rem auto; padding: 2rem;">
        <h1>{{ __('Create your account') }}</h1>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div>
                <label for="name">{{ __('Name') }}</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
                @error('name') <p style="color:red;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email">{{ __('Email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                @error('email') <p style="color:red;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="phone">{{ __('Phone (optional)') }}</label>
                <input id="phone" type="text" name="phone" value="{{ old('phone') }}" placeholder="01XXXXXXXXX">
                @error('phone') <p style="color:red;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password">{{ __('Password') }}</label>
                <input id="password" type="password" name="password" required>
                @error('password') <p style="color:red;">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation">{{ __('Confirm Password') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required>
            </div>

            <div style="margin-top: 1rem;">
                <button type="submit">{{ __('Register') }}</button>
            </div>

            <p>{{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Login') }}</a></p>
        </form>
    </div>
</x-layouts.guest>
