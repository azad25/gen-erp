<?php

namespace Tests\Unit\Domain\Logistics;

use App\Domain\Auth\Models\Company;
use App\Domain\Logistics\Enums\CarrierType;
use App\Domain\Logistics\Models\Carrier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarrierModelTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
    }

    /** @test */
    public function it_can_create_a_carrier()
    {
        $carrier = Carrier::create([
            'company_id' => $this->company->id,
            'name' => 'Pathao',
            'code' => CarrierType::PATHAO,
            'api_endpoint' => 'https://api.pathao.com/v1',
            'api_key' => 'test-key',
            'api_secret' => 'test-secret',
            'is_active' => true,
            'supports_cod' => true,
            'supports_tracking' => true,
            'base_rate' => 50.00,
            'per_kg_rate' => 10.00,
            'cod_charge_percentage' => 1.5,
        ]);

        $this->assertInstanceOf(Carrier::class, $carrier);
        $this->assertEquals('Pathao', $carrier->name);
        $this->assertEquals(CarrierType::PATHAO, $carrier->code);
        $this->assertTrue($carrier->is_active);
        $this->assertTrue($carrier->supports_cod);
        $this->assertTrue($carrier->supports_tracking);
    }

    /** @test */
    public function it_belongs_to_a_company()
    {
        $carrier = Carrier::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Company::class, $carrier->company);
        $this->assertEquals($this->company->id, $carrier->company->id);
    }

    /** @test */
    public function it_can_calculate_shipping_cost()
    {
        $carrier = Carrier::factory()->create([
            'company_id' => $this->company->id,
            'base_rate' => 50.00,
            'per_kg_rate' => 10.00,
            'cod_charge_percentage' => 1.5,
            'supports_cod' => true,
        ]);

        // Test without COD
        $cost = $carrier->calculateShippingCost(2.5, false);
        $this->assertEquals(75.00, $cost); // 50 + (2.5 * 10)

        // Test with COD
        $costWithCOD = $carrier->calculateShippingCost(2.5, true);
        $this->assertEquals(76.13, $costWithCOD); // 75 + (75 * 1.5 / 100) = 75 + 1.125 = 76.125 ≈ 76.13
    }

    /** @test */
    public function it_can_check_if_configured()
    {
        $carrier = Carrier::factory()->create([
            'company_id' => $this->company->id,
            'api_key' => 'test-key',
            'api_endpoint' => 'https://api.test.com',
        ]);

        $this->assertTrue($carrier->isConfigured());

        $carrier->update(['api_key' => null]);
        $this->assertFalse($carrier->isConfigured());
    }

    /** @test */
    public function it_has_active_scope()
    {
        Carrier::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        Carrier::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => false,
        ]);

        $activeCarriers = Carrier::active()->get();
        $this->assertCount(1, $activeCarriers);
        $this->assertTrue($activeCarriers->first()->is_active);
    }

    /** @test */
    public function it_has_company_scope()
    {
        $otherCompany = Company::factory()->create();

        Carrier::factory()->create(['company_id' => $this->company->id]);
        Carrier::factory()->create(['company_id' => $otherCompany->id]);

        $companyCarriers = Carrier::forCompany($this->company->id)->get();
        $this->assertCount(1, $companyCarriers);
        $this->assertEquals($this->company->id, $companyCarriers->first()->company_id);
    }

    /** @test */
    public function it_hides_sensitive_attributes()
    {
        $carrier = Carrier::factory()->create([
            'company_id' => $this->company->id,
            'api_key' => 'secret-key',
            'api_secret' => 'secret-value',
        ]);

        $array = $carrier->toArray();
        $this->assertArrayNotHasKey('api_key', $array);
        $this->assertArrayNotHasKey('api_secret', $array);
    }

    /** @test */
    public function it_casts_code_to_enum()
    {
        $carrier = Carrier::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'pathao',
        ]);

        $this->assertInstanceOf(CarrierType::class, $carrier->code);
        $this->assertEquals(CarrierType::PATHAO, $carrier->code);
    }
}