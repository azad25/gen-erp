<div>
    <h1>{{ __('Set Up Your Company') }}</h1>

        {{-- Step indicator --}}
        <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
            @for ($i = 1; $i <= 3; $i++)
                <span style="font-weight: {{ $currentStep === $i ? 'bold' : 'normal' }}; color: {{ $currentStep >= $i ? '#2563eb' : '#9ca3af' }};">
                    {{ $i }}. {{ match($i) { 1 => __('Basics'), 2 => __('Location'), 3 => __('Confirm') } }}
                </span>
            @endfor
        </div>

        {{-- Step 1: Basics --}}
        @if ($currentStep === 1)
            <div>
                <div>
                    <label for="name">{{ __('Company Name') }} *</label>
                    <input id="name" type="text" wire:model="name" required>
                    @error('name') <p style="color:red;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="business_type">{{ __('Business Type') }} *</label>
                    <select id="business_type" wire:model="business_type" required>
                        <option value="">{{ __('Select...') }}</option>
                        @foreach ($this->businessTypeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('business_type') <p style="color:red;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="wizard_phone">{{ __('Phone') }}</label>
                    <input id="wizard_phone" type="text" wire:model="phone" placeholder="01XXXXXXXXX">
                    @error('phone') <p style="color:red;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="wizard_email">{{ __('Company Email') }}</label>
                    <input id="wizard_email" type="email" wire:model="email">
                    @error('email') <p style="color:red;">{{ $message }}</p> @enderror
                </div>

                <div style="margin-top: 1rem;">
                    <button wire:click="nextStep" type="button">{{ __('Next →') }}</button>
                </div>
            </div>
        @endif

        {{-- Step 2: Location --}}
        @if ($currentStep === 2)
            <div>
                <div>
                    <label for="address_line1">{{ __('Address') }}</label>
                    <input id="address_line1" type="text" wire:model="address_line1">
                    @error('address_line1') <p style="color:red;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="city">{{ __('City') }}</label>
                    <input id="city" type="text" wire:model="city">
                    @error('city') <p style="color:red;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="district">{{ __('District') }}</label>
                    <select id="district" wire:model="district">
                        <option value="">{{ __('Select district...') }}</option>
                        @foreach ($this->districts as $dist)
                            <option value="{{ $dist }}">{{ $dist }}</option>
                        @endforeach
                    </select>
                    @error('district') <p style="color:red;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="postal_code">{{ __('Postal Code') }}</label>
                    <input id="postal_code" type="text" wire:model="postal_code">
                    @error('postal_code') <p style="color:red;">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label>
                        <input type="checkbox" wire:model.live="vat_registered">
                        {{ __('VAT Registered?') }}
                    </label>
                </div>

                @if ($vat_registered)
                    <div>
                        <label for="vat_bin">{{ __('VAT BIN') }}</label>
                        <input id="vat_bin" type="text" wire:model="vat_bin">
                        @error('vat_bin') <p style="color:red;">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                    <button wire:click="previousStep" type="button">{{ __('← Back') }}</button>
                    <button wire:click="nextStep" type="button">{{ __('Next →') }}</button>
                </div>
            </div>
        @endif

        {{-- Step 3: Confirm --}}
        @if ($currentStep === 3)
            <div>
                <h2>{{ __('Review & Confirm') }}</h2>

                <table style="width: 100%; border-collapse: collapse;">
                    <tr><td style="padding: 0.5rem; font-weight: bold;">{{ __('Company Name') }}</td><td>{{ $name }}</td></tr>
                    <tr><td style="padding: 0.5rem; font-weight: bold;">{{ __('Business Type') }}</td><td>{{ \App\Enums\BusinessType::tryFrom($business_type)?->label() ?? '-' }}</td></tr>
                    <tr><td style="padding: 0.5rem; font-weight: bold;">{{ __('Phone') }}</td><td>{{ $phone ?: '-' }}</td></tr>
                    <tr><td style="padding: 0.5rem; font-weight: bold;">{{ __('Email') }}</td><td>{{ $email ?: '-' }}</td></tr>
                    <tr><td style="padding: 0.5rem; font-weight: bold;">{{ __('Address') }}</td><td>{{ $address_line1 ?: '-' }}</td></tr>
                    <tr><td style="padding: 0.5rem; font-weight: bold;">{{ __('City') }}</td><td>{{ $city ?: '-' }}</td></tr>
                    <tr><td style="padding: 0.5rem; font-weight: bold;">{{ __('District') }}</td><td>{{ $district ?: '-' }}</td></tr>
                    <tr><td style="padding: 0.5rem; font-weight: bold;">{{ __('Postal Code') }}</td><td>{{ $postal_code ?: '-' }}</td></tr>
                    <tr><td style="padding: 0.5rem; font-weight: bold;">{{ __('VAT Registered') }}</td><td>{{ $vat_registered ? __('Yes') . ' — BIN: ' . $vat_bin : __('No') }}</td></tr>
                </table>

                <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                    <button wire:click="previousStep" type="button">{{ __('← Back') }}</button>
                    <button wire:click="submit" type="button" style="font-weight: bold;">{{ __('Create Company') }}</button>
                </div>
            </div>
        @endif
    </div>
</div>
