<?php

namespace App\Livewire\Setup;

use App\Enums\BusinessType;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Services\BusinessTypeTemplateService;
use App\Services\CompanyContext;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Multi-step company setup wizard for new users.
 */
class CompanySetupWizard extends Component
{
    public int $currentStep = 1;

    // Step 1 — Basics
    public string $name = '';

    public string $business_type = '';

    public string $phone = '';

    public string $email = '';

    // Step 2 — Location
    public string $address_line1 = '';

    public string $city = '';

    public string $district = '';

    public string $postal_code = '';

    public bool $vat_registered = false;

    public string $vat_bin = '';

    /**
     * Navigate to the next step with validation.
     */
    public function nextStep(): void
    {
        $this->validateCurrentStep();
        $this->currentStep++;
    }

    /**
     * Navigate to the previous step.
     */
    public function previousStep(): void
    {
        $this->currentStep = max(1, $this->currentStep - 1);
    }

    /**
     * Submit the wizard and create the company.
     */
    public function submit(BusinessTypeTemplateService $templateService): void
    {
        $this->validateCurrentStep();

        $user = auth()->user();

        $company = Company::create([
            'uuid' => Str::uuid()->toString(),
            'name' => $this->name,
            'slug' => Str::slug($this->name).'-'.Str::random(6),
            'business_type' => $this->business_type,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'address_line1' => $this->address_line1 ?: null,
            'city' => $this->city ?: null,
            'district' => $this->district ?: null,
            'postal_code' => $this->postal_code ?: null,
            'vat_registered' => $this->vat_registered,
            'vat_bin' => $this->vat_registered ? $this->vat_bin : null,
            'onboarding_completed_at' => now(),
        ]);

        // Create the owner pivot
        CompanyUser::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_owner' => true,
            'joined_at' => now(),
            'is_active' => true,
        ]);

        // Set as user's active company
        $user->update(['last_active_company_id' => $company->id]);

        // Apply business type templates
        CompanyContext::setActive($company);
        $templateService->apply($company);

        $this->redirect('/app');
    }

    /**
     * Get business type options for the form.
     *
     * @return array<string, string>
     */
    public function getBusinessTypeOptionsProperty(): array
    {
        return BusinessType::options();
    }

    /**
     * Get all 64 Bangladesh districts.
     *
     * @return array<int, string>
     */
    public function getDistrictsProperty(): array
    {
        return [
            'Bagerhat', 'Bandarban', 'Barguna', 'Barisal', 'Bhola',
            'Bogra', 'Brahmanbaria', 'Chandpur', 'Chapainawabganj', 'Chittagong',
            'Chuadanga', 'Comilla', 'Cox\'s Bazar', 'Dhaka', 'Dinajpur',
            'Faridpur', 'Feni', 'Gaibandha', 'Gazipur', 'Gopalganj',
            'Habiganj', 'Jamalpur', 'Jessore', 'Jhalokati', 'Jhenaidah',
            'Joypurhat', 'Khagrachhari', 'Khulna', 'Kishoreganj', 'Kurigram',
            'Kushtia', 'Lakshmipur', 'Lalmonirhat', 'Madaripur', 'Magura',
            'Manikganj', 'Meherpur', 'Moulvibazar', 'Munshiganj', 'Mymensingh',
            'Naogaon', 'Narail', 'Narayanganj', 'Narsingdi', 'Natore',
            'Nawabganj', 'Netrokona', 'Nilphamari', 'Noakhali', 'Pabna',
            'Panchagarh', 'Patuakhali', 'Pirojpur', 'Rajbari', 'Rajshahi',
            'Rangamati', 'Rangpur', 'Satkhira', 'Shariatpur', 'Sherpur',
            'Sirajganj', 'Sunamganj', 'Sylhet', 'Tangail',
        ];
    }

    /**
     * Validate rules for the current step.
     */
    private function validateCurrentStep(): void
    {
        match ($this->currentStep) {
            1 => $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'business_type' => ['required', 'string', Rule::enum(BusinessType::class)],
                'phone' => ['nullable', 'string', 'max:20', 'regex:/^01[3-9]\d{8}$/'],
                'email' => ['nullable', 'string', 'email', 'max:255'],
            ]),
            2 => $this->validate([
                'address_line1' => ['nullable', 'string', 'max:255'],
                'city' => ['nullable', 'string', 'max:100'],
                'district' => ['nullable', 'string', 'max:100'],
                'postal_code' => ['nullable', 'string', 'max:20'],
                'vat_registered' => ['nullable', 'boolean'],
                'vat_bin' => ['nullable', 'required_if:vat_registered,true', 'string', 'max:20'],
            ]),
            3 => null, // Confirmation step — no additional validation
        };
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.setup.company-setup-wizard');
    }
}
