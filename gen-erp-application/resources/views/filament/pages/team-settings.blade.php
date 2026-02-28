<x-filament-panels::page>
    {{-- Members Table --}}
    <div>
        {{ $this->table }}
    </div>

    {{-- Invite Form --}}
    <x-filament::section>
        <x-slot name="heading">{{ __('Invite New Member') }}</x-slot>

        <form wire:submit="sendInvitation">
            <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 1rem; align-items: end;">
                <div>
                    <label for="invite_email" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">{{ __('Email') }}</label>
                    <input id="invite_email" type="email" wire:model="invite_email" required style="width: 100%; padding: 0.5rem; border: 1px solid var(--filament-gray-300); border-radius: 0.375rem;">
                    @error('invite_email') <p style="color: red; font-size: 0.75rem;">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="invite_role" style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">{{ __('Role') }}</label>
                    <select id="invite_role" wire:model="invite_role" required style="width: 100%; padding: 0.5rem; border: 1px solid var(--filament-gray-300); border-radius: 0.375rem;">
                        <option value="">{{ __('Select role...') }}</option>
                        @foreach (\App\Enums\CompanyRole::options() as $value => $label)
                            @if ($value !== 'owner')
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('invite_role') <p style="color: red; font-size: 0.75rem;">{{ $message }}</p> @enderror
                </div>
                <x-filament::button type="submit">
                    {{ __('Send Invite') }}
                </x-filament::button>
            </div>
        </form>
    </x-filament::section>

    {{-- Pending Invitations --}}
    @if ($this->pendingInvitations->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">{{ __('Pending Invitations') }}</x-slot>

            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--filament-gray-200);">
                        <th style="text-align: left; padding: 0.5rem;">{{ __('Email') }}</th>
                        <th style="text-align: left; padding: 0.5rem;">{{ __('Role') }}</th>
                        <th style="text-align: left; padding: 0.5rem;">{{ __('Expires') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->pendingInvitations as $invitation)
                        <tr style="border-bottom: 1px solid var(--filament-gray-100);">
                            <td style="padding: 0.5rem;">{{ $invitation->email }}</td>
                            <td style="padding: 0.5rem;">{{ \App\Enums\CompanyRole::tryFrom($invitation->role)?->label() ?? $invitation->role }}</td>
                            <td style="padding: 0.5rem;">{{ $invitation->expires_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
