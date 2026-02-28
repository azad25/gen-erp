<div class="flex items-center justify-center min-h-screen p-4 bg-slate-50">
    <!-- Background Accents -->
    <div class="fixed top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-[var(--color-primary-light)] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-[var(--color-primary)] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
    </div>

    <div class="relative z-10 w-full max-w-3xl overflow-hidden bg-white border border-slate-100 shadow-xl rounded-2xl">
        <div class="flex flex-col md:flex-row">
            <!-- Sidebar / Steps Indicator -->
            <div class="p-8 text-white bg-slate-900 md:w-1/3">
                <div class="flex items-center gap-2 mb-10">
                    <x-home.logo class="w-8 h-8" />
                    <span class="text-xl font-bold tracking-tight">GenERP</span>
                </div>
                
                <h2 class="mb-2 text-2xl font-bold">{{ __('Set Up Your Company') }}</h2>
                <p class="mb-8 text-sm text-slate-400">Let's get your workspace customized and ready.</p>

                <div class="space-y-6">
                    @foreach([1 => __('Basics'), 2 => __('Location'), 3 => __('Confirm')] as $stepNum => $stepLabel)
                        <div class="flex items-center gap-4">
                            <div class="flex items-center justify-center w-8 h-8 font-semibold rounded-full border-2 transition-colors 
                                {{ $currentStep === $stepNum ? 'border-[var(--color-primary-light)] text-[var(--color-primary-light)]' : ($currentStep > $stepNum ? 'border-emerald-400 text-emerald-400 bg-emerald-400/10' : 'border-slate-700 text-slate-600') }}">
                                @if($currentStep > $stepNum)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    {{ $stepNum }}
                                @endif
                            </div>
                            <span class="font-medium {{ $currentStep === $stepNum ? 'text-white' : ($currentStep > $stepNum ? 'text-emerald-400' : 'text-slate-500') }}">
                                {{ $stepLabel }}
                            </span>
                        </div>
                        @if($stepNum < 3)
                            <div class="w-0.5 h-6 bg-slate-800 ml-4 -my-2"></div>
                        @endif
                    @endforeach
                </div>
            </div>

            <!-- Content Area -->
            <div class="p-8 md:w-2/3 lg:p-12">
                {{-- Step 1: Basics --}}
                @if ($currentStep === 1)
                    <div class="animate-fadeIn">
                        <h3 class="mb-6 text-xl font-bold text-slate-800">{{ __('Company Basics') }}</h3>
                        
                        <div class="space-y-5">
                            <div>
                                <label for="name" class="block mb-1.5 text-sm font-medium text-slate-700">{{ __('Company Name') }} <span class="text-red-500">*</span></label>
                                <input id="name" type="text" wire:model="name" required class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary-light)] sm:text-sm" placeholder="Acme Corporation">
                                @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="business_type" class="block mb-1.5 text-sm font-medium text-slate-700">{{ __('Business Type') }} <span class="text-red-500">*</span></label>
                                <select id="business_type" wire:model="business_type" required class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary-light)] sm:text-sm">
                                    <option value="">{{ __('Select business type...') }}</option>
                                    @foreach ($this->businessTypeOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('business_type') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label for="wizard_phone" class="block mb-1.5 text-sm font-medium text-slate-700">{{ __('Phone') }}</label>
                                    <input id="wizard_phone" type="text" wire:model="phone" class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary-light)] sm:text-sm" placeholder="01XXXXXXXXX">
                                    @error('phone') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="wizard_email" class="block mb-1.5 text-sm font-medium text-slate-700">{{ __('Company Email') }}</label>
                                    <input id="wizard_email" type="email" wire:model="email" class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary-light)] sm:text-sm" placeholder="contact@company.com">
                                    @error('email') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end mt-10">
                            <button wire:click="nextStep" type="button" class="px-6 py-2.5 text-sm font-medium text-white transition-all bg-[var(--color-primary)] hover:bg-[#115e59] rounded-lg shadow hover:shadow-md">
                                {{ __('Next Step') }} &rarr;
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Step 2: Location --}}
                @if ($currentStep === 2)
                    <div class="animate-fadeIn">
                        <h3 class="mb-6 text-xl font-bold text-slate-800">{{ __('Location & Details') }}</h3>
                        
                        <div class="space-y-5">
                            <div>
                                <label for="address_line1" class="block mb-1.5 text-sm font-medium text-slate-700">{{ __('Street Address') }}</label>
                                <input id="address_line1" type="text" wire:model="address_line1" class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary-light)] sm:text-sm" placeholder="123 Business Rd">
                                @error('address_line1') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label for="city" class="block mb-1.5 text-sm font-medium text-slate-700">{{ __('City') }}</label>
                                    <input id="city" type="text" wire:model="city" class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary-light)] sm:text-sm" placeholder="Dhaka">
                                    @error('city') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>

                                <div>
                                    <label for="postal_code" class="block mb-1.5 text-sm font-medium text-slate-700">{{ __('Postal Code') }}</label>
                                    <input id="postal_code" type="text" wire:model="postal_code" class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary-light)] sm:text-sm" placeholder="1200">
                                    @error('postal_code') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label for="district" class="block mb-1.5 text-sm font-medium text-slate-700">{{ __('District') }}</label>
                                <select id="district" wire:model="district" class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary-light)] sm:text-sm">
                                    <option value="">{{ __('Select district...') }}</option>
                                    @foreach ($this->districts as $dist)
                                        <option value="{{ $dist }}">{{ $dist }}</option>
                                    @endforeach
                                </select>
                                @error('district') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                            </div>

                            <div class="p-4 mt-2 border border-slate-200 rounded-lg bg-slate-50">
                                <label class="flex items-center cursor-pointer">
                                    <div class="relative flex items-center">
                                        <input type="checkbox" wire:model.live="vat_registered" class="w-5 h-5 text-[var(--color-primary)] border-slate-300 rounded focus:ring-[var(--color-primary-light)]">
                                    </div>
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-slate-800">{{ __('Company is VAT Registered') }}</span>
                                    </div>
                                </label>

                                @if ($vat_registered)
                                    <div class="mt-4 animate-fadeIn">
                                        <label for="vat_bin" class="block mb-1.5 text-sm font-medium text-slate-700">{{ __('VAT BIN') }} <span class="text-red-500">*</span></label>
                                        <input id="vat_bin" type="text" wire:model="vat_bin" class="block w-full px-4 py-3 text-slate-900 transition-colors bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary-light)] sm:text-sm" placeholder="000123456-0101">
                                        @error('vat_bin') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex justify-between mt-10">
                            <button wire:click="previousStep" type="button" class="px-6 py-2.5 text-sm font-medium text-slate-600 transition-colors bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                                &larr; {{ __('Back') }}
                            </button>
                            <button wire:click="nextStep" type="button" class="px-6 py-2.5 text-sm font-medium text-white transition-all bg-[var(--color-primary)] hover:bg-[#115e59] rounded-lg shadow hover:shadow-md">
                                {{ __('Next Step') }} &rarr;
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Step 3: Confirm --}}
                @if ($currentStep === 3)
                    <div class="animate-fadeIn">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full bg-emerald-100 text-emerald-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <h3 class="text-xl font-bold text-slate-800">{{ __('Review & Confirm') }}</h3>
                        </div>
                        
                        <div class="p-6 mb-8 border border-slate-200 rounded-xl bg-slate-50">
                            <dl class="space-y-4 text-sm">
                                <div class="grid grid-cols-3 gap-4 pb-4 border-b border-slate-200">
                                    <dt class="font-medium text-slate-500">{{ __('Company Name') }}</dt>
                                    <dd class="col-span-2 font-semibold text-slate-900">{{ $name }}</dd>
                                </div>
                                <div class="grid grid-cols-3 gap-4 pb-4 border-b border-slate-200">
                                    <dt class="font-medium text-slate-500">{{ __('Business Type') }}</dt>
                                    <dd class="col-span-2 text-slate-900">{{ \App\Enums\BusinessType::tryFrom($business_type)?->label() ?? '-' }}</dd>
                                </div>
                                <div class="grid grid-cols-3 gap-4 pb-4 border-b border-slate-200">
                                    <dt class="font-medium text-slate-500">{{ __('Contact') }}</dt>
                                    <dd class="col-span-2 text-slate-900">
                                        @if($email)<div>{{ $email }}</div>@endif
                                        @if($phone)<div class="text-slate-500">{{ $phone }}</div>@endif
                                        @if(!$email && !$phone) - @endif
                                    </dd>
                                </div>
                                <div class="grid grid-cols-3 gap-4 pb-4 border-b border-slate-200">
                                    <dt class="font-medium text-slate-500">{{ __('Address') }}</dt>
                                    <dd class="col-span-2 text-slate-900">
                                        @if($address_line1)<div>{{ $address_line1 }}</div>@endif
                                        <div class="text-slate-500">
                                            {{ collect([$city, $district, $postal_code])->filter()->join(', ') ?: '-' }}
                                        </div>
                                    </dd>
                                </div>
                                <div class="grid grid-cols-3 gap-4">
                                    <dt class="font-medium text-slate-500">{{ __('VAT Registration') }}</dt>
                                    <dd class="col-span-2 text-slate-900">
                                        @if($vat_registered)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">{{ __('Registered') }}</span>
                                            <div class="mt-1 text-sm text-slate-500">BIN: {{ $vat_bin }}</div>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">{{ __('Not Registered') }}</span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div class="flex justify-between mt-10">
                            <button wire:click="previousStep" type="button" class="px-6 py-2.5 text-sm font-medium text-slate-600 transition-colors bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                                &larr; {{ __('Back') }}
                            </button>
                            <button wire:click="submit" type="button" class="px-8 py-2.5 text-sm font-medium text-white transition-all bg-[var(--color-primary)] hover:bg-[#115e59] rounded-lg shadow-lg hover:shadow-[var(--color-primary)]/30 flex items-center gap-2">
                                {{ __('Create Workspace') }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fadeIn {
            animation: fadeIn 0.4s ease-out forwards;
        }
    </style>
</div>
