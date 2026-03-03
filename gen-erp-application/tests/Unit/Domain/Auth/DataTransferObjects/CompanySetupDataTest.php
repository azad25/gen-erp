<?php

namespace Tests\Unit\Domain\Auth\DataTransferObjects;

use App\Domain\Auth\DataTransferObjects\CompanySetupData;
use Tests\TestCase;

class CompanySetupDataTest extends TestCase
{
    /** @test */
    public function it_can_be_created_with_required_fields_only(): void
    {
        // Act
        $data = new CompanySetupData(
            name: 'Test Company',
            businessType: 'retail'
        );

        // Assert
        $this->assertEquals('Test Company', $data->name);
        $this->assertEquals('retail', $data->businessType);
        $this->assertEquals('BD', $data->country);
        $this->assertEquals('BDT', $data->currency);
        $this->assertEquals('Asia/Dhaka', $data->timezone);
        $this->assertEquals('en', $data->locale);
        $this->assertEquals('free', $data->plan);
    }

    /** @test */
    public function it_can_be_created_with_all_fields(): void
    {
        // Act
        $data = new CompanySetupData(
            name: 'Full Company Ltd',
            businessType: 'manufacturing',
            country: 'US',
            currency: 'USD',
            timezone: 'America/New_York',
            locale: 'en',
            addressLine1: '123 Main St',
            addressLine2: 'Suite 100',
            city: 'New York',
            district: 'Manhattan',
            postalCode: '10001',
            phone: '01712345678',
            email: 'company@example.com',
            website: 'https://example.com',
            vatBin: '123456789012',
            tradeLicense: 'TL123456',
            tin: '987654321098',
            plan: 'premium'
        );

        // Assert
        $this->assertEquals('Full Company Ltd', $data->name);
        $this->assertEquals('manufacturing', $data->businessType);
        $this->assertEquals('US', $data->country);
        $this->assertEquals('USD', $data->currency);
        $this->assertEquals('America/New_York', $data->timezone);
        $this->assertEquals('en', $data->locale);
        $this->assertEquals('123 Main St', $data->addressLine1);
        $this->assertEquals('Suite 100', $data->addressLine2);
        $this->assertEquals('New York', $data->city);
        $this->assertEquals('Manhattan', $data->district);
        $this->assertEquals('10001', $data->postalCode);
        $this->assertEquals('01712345678', $data->phone);
        $this->assertEquals('company@example.com', $data->email);
        $this->assertEquals('https://example.com', $data->website);
        $this->assertEquals('123456789012', $data->vatBin);
        $this->assertEquals('TL123456', $data->tradeLicense);
        $this->assertEquals('987654321098', $data->tin);
        $this->assertEquals('premium', $data->plan);
    }

    /** @test */
    public function it_can_be_created_from_array(): void
    {
        // Arrange
        $array = [
            'name' => 'Array Company',
            'business_type' => 'service',
            'country' => 'BD',
            'currency' => 'BDT',
            'address_line1' => '456 Test St',
            'city' => 'Dhaka',
            'phone' => '01987654321',
            'vat_bin' => '987654321012',
        ];

        // Act
        $data = CompanySetupData::fromArray($array);

        // Assert
        $this->assertEquals('Array Company', $data->name);
        $this->assertEquals('service', $data->businessType);
        $this->assertEquals('BD', $data->country);
        $this->assertEquals('BDT', $data->currency);
        $this->assertEquals('456 Test St', $data->addressLine1);
        $this->assertEquals('Dhaka', $data->city);
        $this->assertEquals('01987654321', $data->phone);
        $this->assertEquals('987654321012', $data->vatBin);
    }

    /** @test */
    public function it_uses_defaults_when_creating_from_minimal_array(): void
    {
        // Arrange
        $array = [
            'name' => 'Minimal Company',
            'business_type' => 'retail',
        ];

        // Act
        $data = CompanySetupData::fromArray($array);

        // Assert
        $this->assertEquals('Minimal Company', $data->name);
        $this->assertEquals('retail', $data->businessType);
        $this->assertEquals('BD', $data->country);
        $this->assertEquals('BDT', $data->currency);
        $this->assertEquals('Asia/Dhaka', $data->timezone);
        $this->assertEquals('en', $data->locale);
        $this->assertEquals('free', $data->plan);
    }

    /** @test */
    public function it_can_be_converted_to_array(): void
    {
        // Arrange
        $data = new CompanySetupData(
            name: 'Test Company',
            businessType: 'retail',
            vatBin: '123456789012'
        );

        // Act
        $array = $data->toArray();

        // Assert
        $this->assertEquals([
            'name' => 'Test Company',
            'business_type' => 'retail',
            'country' => 'BD',
            'currency' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'locale' => 'en',
            'address_line1' => null,
            'address_line2' => null,
            'city' => null,
            'district' => null,
            'postal_code' => null,
            'phone' => null,
            'email' => null,
            'website' => null,
            'vat_bin' => '123456789012',
            'trade_license' => null,
            'tin' => null,
            'plan' => 'free',
            'vat_registered' => true, // Because vat_bin is provided
            'is_active' => true,
        ], $array);
    }

    /** @test */
    public function it_sets_vat_registered_false_when_no_vat_bin(): void
    {
        // Arrange
        $data = new CompanySetupData(
            name: 'Test Company',
            businessType: 'retail'
        );

        // Act
        $array = $data->toArray();

        // Assert
        $this->assertFalse($array['vat_registered']);
        $this->assertNull($array['vat_bin']);
    }

    /** @test */
    public function it_generates_slug_from_name(): void
    {
        // Arrange
        $data = new CompanySetupData(
            name: 'Test Company Ltd & Co.',
            businessType: 'retail'
        );

        // Act
        $slug = $data->getSlug();

        // Assert
        $this->assertEquals('test-company-ltd-co', $slug);
    }

    /** @test */
    public function it_generates_uuid(): void
    {
        // Arrange
        $data = new CompanySetupData(
            name: 'Test Company',
            businessType: 'retail'
        );

        // Act
        $uuid1 = $data->getUuid();
        $uuid2 = $data->getUuid();

        // Assert
        $this->assertIsString($uuid1);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid1);
        $this->assertNotEquals($uuid1, $uuid2); // Each call generates new UUID
    }
}
