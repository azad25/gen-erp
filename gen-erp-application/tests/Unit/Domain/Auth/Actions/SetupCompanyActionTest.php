<?php

namespace Tests\Unit\Domain\Auth\Actions;

use App\Domain\Auth\Actions\SetupCompanyAction;
use App\Domain\Auth\DataTransferObjects\CompanySetupData;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Support\Enums\BusinessType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupCompanyActionTest extends TestCase
{
    use RefreshDatabase;

    private SetupCompanyAction $action;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new SetupCompanyAction;
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_can_setup_company_for_user(): void
    {
        // Arrange
        $companyData = new CompanySetupData(
            name: 'Test Company Ltd',
            businessType: 'retail',
            country: 'BD',
            currency: 'BDT',
            timezone: 'Asia/Dhaka',
            locale: 'en',
            addressLine1: '123 Main Street',
            city: 'Dhaka',
            district: 'Dhaka',
            phone: '01712345678',
            email: 'company@example.com',
            vatBin: '123456789012'
        );

        // Act
        $company = $this->action->execute($this->user, $companyData);

        // Assert
        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals('Test Company Ltd', $company->name);
        $this->assertEquals('test-company-ltd', $company->slug);
        $this->assertEquals(BusinessType::RETAIL, $company->business_type);
        $this->assertEquals('BD', $company->country);
        $this->assertEquals('BDT', $company->currency);
        $this->assertEquals('Asia/Dhaka', $company->timezone);
        $this->assertEquals('en', $company->locale);
        $this->assertEquals('123 Main Street', $company->address_line1);
        $this->assertEquals('Dhaka', $company->city);
        $this->assertEquals('Dhaka', $company->district);
        $this->assertEquals('01712345678', $company->phone);
        $this->assertEquals('company@example.com', $company->email);
        $this->assertEquals('123456789012', $company->vat_bin);
        $this->assertTrue($company->vat_registered);
        $this->assertTrue($company->is_active);
        $this->assertNotNull($company->uuid);
    }

    /** @test */
    public function it_attaches_user_as_owner(): void
    {
        // Arrange
        $companyData = new CompanySetupData(
            name: 'Test Company',
            businessType: 'service'
        );

        // Act
        $company = $this->action->execute($this->user, $companyData);

        // Assert
        $this->assertTrue($this->user->companies()->where('companies.id', $company->id)->exists());

        $pivot = $this->user->companies()->where('companies.id', $company->id)->first()->pivot;
        $this->assertEquals('owner', $pivot->role);
        $this->assertEquals(1, $pivot->is_owner); // Database stores as integer
        $this->assertEquals(1, $pivot->is_active); // Database stores as integer
        $this->assertNotNull($pivot->joined_at);
    }

    /** @test */
    public function it_updates_user_last_active_company(): void
    {
        // Arrange
        $companyData = new CompanySetupData(
            name: 'Test Company',
            businessType: 'service'
        );

        // Act
        $company = $this->action->execute($this->user, $companyData);

        // Assert
        $this->user->refresh();
        $this->assertEquals($company->id, $this->user->last_active_company_id);
    }

    /** @test */
    public function it_uses_user_email_when_company_email_not_provided(): void
    {
        // Arrange
        $companyData = new CompanySetupData(
            name: 'Test Company',
            businessType: 'service'
        );

        // Act
        $company = $this->action->execute($this->user, $companyData);

        // Assert
        $this->assertEquals($this->user->email, $company->email);
    }

    /** @test */
    public function it_sets_vat_registered_false_when_no_vat_bin(): void
    {
        // Arrange
        $companyData = new CompanySetupData(
            name: 'Test Company',
            businessType: 'service'
        );

        // Act
        $company = $this->action->execute($this->user, $companyData);

        // Assert
        $this->assertNull($company->vat_bin);
        $this->assertFalse($company->vat_registered);
    }

    /** @test */
    public function it_generates_unique_uuid_and_slug(): void
    {
        // Arrange
        $companyData1 = new CompanySetupData(
            name: 'Test Company Alpha',
            businessType: 'service'
        );
        $companyData2 = new CompanySetupData(
            name: 'Test Company Beta',
            businessType: 'retail'
        );
        $user2 = User::factory()->create();

        // Act
        $company1 = $this->action->execute($this->user, $companyData1);
        $company2 = $this->action->execute($user2, $companyData2);

        // Assert
        $this->assertNotEquals($company1->uuid, $company2->uuid);
        $this->assertEquals('test-company-alpha', $company1->slug);
        $this->assertEquals('test-company-beta', $company2->slug);
    }
}
