<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates company setup input with Bangladesh-specific validations.
 */
class CompanySetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'in:retail,pharmacy,wholesale,manufacturing,rmg,restaurant,service,freelancer,ngo,ecommerce,school,government,other'],
            'country' => ['nullable', 'string', 'max:2', 'in:BD,US,UK,CA,AU'],
            'currency' => ['nullable', 'string', 'max:3', 'in:BDT,USD,EUR,GBP,CAD,AUD'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'locale' => ['nullable', 'string', 'max:5', 'in:en,bn'],

            // Address fields
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100', 'in:'.$this->getBangladeshDistricts()],
            'postal_code' => ['nullable', 'string', 'max:20', 'regex:/^\d{4}$/'],

            // Contact fields
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+880|880|0)?1[3-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],

            // Tax fields
            'vat_bin' => ['nullable', 'string', 'max:20', 'regex:/^\d{12}$/'],
            'trade_license' => ['nullable', 'string', 'max:50'],
            'tin' => ['nullable', 'string', 'max:20', 'regex:/^\d{12}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('Please enter a valid Bangladeshi mobile number (e.g. 01712345678).'),
            'postal_code.regex' => __('Please enter a valid 4-digit postal code.'),
            'vat_bin.regex' => __('VAT BIN must be 12 digits.'),
            'tin.regex' => __('TIN must be 12 digits.'),
            'district.in' => __('Please select a valid district.'),
        ];
    }

    /**
     * Get the validation attributes.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('Company Name'),
            'business_type' => __('Business Type'),
            'address_line1' => __('Address Line 1'),
            'address_line2' => __('Address Line 2'),
            'vat_bin' => __('VAT BIN'),
            'trade_license' => __('Trade License'),
            'tin' => __('TIN'),
        ];
    }

    /**
     * Get Bangladesh districts for validation.
     */
    private function getBangladeshDistricts(): string
    {
        return implode(',', [
            'Bagerhat', 'Bandarban', 'Barguna', 'Barishal', 'Bhola', 'Bogura', 'Brahmanbaria',
            'Chandpur', 'Chattogram', 'Chuadanga', 'Comilla', 'Cox\'s Bazar', 'Dhaka',
            'Dinajpur', 'Faridpur', 'Feni', 'Gaibandha', 'Gazipur', 'Gopalganj', 'Habiganj',
            'Jamalpur', 'Jashore', 'Jhalokati', 'Jhenaidah', 'Joypurhat', 'Khagrachhari',
            'Khulna', 'Kishoreganj', 'Kurigram', 'Kushtia', 'Lakshmipur', 'Lalmonirhat',
            'Madaripur', 'Magura', 'Manikganj', 'Meherpur', 'Moulvibazar', 'Munshiganj',
            'Mymensingh', 'Naogaon', 'Narail', 'Narayanganj', 'Narsingdi', 'Natore',
            'Netrokona', 'Nilphamari', 'Noakhali', 'Pabna', 'Panchagarh', 'Patuakhali',
            'Pirojpur', 'Rajbari', 'Rajshahi', 'Rangamati', 'Rangpur', 'Satkhira',
            'Shariatpur', 'Sherpur', 'Sirajganj', 'Sunamganj', 'Sylhet', 'Tangail',
            'Thakurgaon',
        ]);
    }
}
