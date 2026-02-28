<x-layouts.guest :title="__('Invitation Expired')">
    <div style="max-width: 480px; margin: 4rem auto; padding: 2rem; text-align: center;">
        <h1>{{ __('Invitation Expired') }}</h1>
        <p>{{ __('This invitation to join :company has expired. Please ask the company owner to send a new invitation.', ['company' => $invitation->company->name]) }}</p>
        <a href="{{ route('login') }}">{{ __('Go to Login') }}</a>
    </div>
</x-layouts.guest>
