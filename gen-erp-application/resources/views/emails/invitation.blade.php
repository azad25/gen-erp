<x-mail::message>
# {{ __('You\'ve been invited!') }}

**{{ $invitation->invitedBy?->name }}** {{ __('has invited you to join') }} **{{ $invitation->company->name }}** {{ __('on GenERP BD.') }}

**{{ __('Role:') }}** {{ \App\Enums\CompanyRole::tryFrom($invitation->role)?->label() ?? $invitation->role }}

<x-mail::button :url="$acceptUrl">
{{ __('Accept Invitation') }}
</x-mail::button>

{{ __('This invitation expires') }} {{ $invitation->expires_at->diffForHumans() }}.

{{ __('If you did not expect this invitation, you can ignore this email.') }}

{{ __('Thanks,') }}<br>
{{ config('app.name') }}
</x-mail::message>
