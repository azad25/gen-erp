<?php

namespace Tests\Unit\Domain\Auth\DataTransferObjects;

use App\Domain\Auth\DataTransferObjects\UserRegistrationData;
use Tests\TestCase;

class UserRegistrationDataTest extends TestCase
{
    /** @test */
    public function it_can_be_created_with_all_fields(): void
    {
        // Act
        $data = new UserRegistrationData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            phone: '01712345678'
        );

        // Assert
        $this->assertEquals('John Doe', $data->name);
        $this->assertEquals('john@example.com', $data->email);
        $this->assertEquals('password123', $data->password);
        $this->assertEquals('01712345678', $data->phone);
    }

    /** @test */
    public function it_can_be_created_without_phone(): void
    {
        // Act
        $data = new UserRegistrationData(
            name: 'Jane Doe',
            email: 'jane@example.com',
            password: 'password123'
        );

        // Assert
        $this->assertEquals('Jane Doe', $data->name);
        $this->assertEquals('jane@example.com', $data->email);
        $this->assertEquals('password123', $data->password);
        $this->assertNull($data->phone);
    }

    /** @test */
    public function it_can_be_created_from_array(): void
    {
        // Arrange
        $array = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
            'phone' => '01987654321',
        ];

        // Act
        $data = UserRegistrationData::fromArray($array);

        // Assert
        $this->assertEquals('Test User', $data->name);
        $this->assertEquals('test@example.com', $data->email);
        $this->assertEquals('secret123', $data->password);
        $this->assertEquals('01987654321', $data->phone);
    }

    /** @test */
    public function it_can_be_created_from_array_without_phone(): void
    {
        // Arrange
        $array = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
        ];

        // Act
        $data = UserRegistrationData::fromArray($array);

        // Assert
        $this->assertEquals('Test User', $data->name);
        $this->assertEquals('test@example.com', $data->email);
        $this->assertEquals('secret123', $data->password);
        $this->assertNull($data->phone);
    }

    /** @test */
    public function it_can_be_converted_to_array(): void
    {
        // Arrange
        $data = new UserRegistrationData(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123',
            phone: '01712345678'
        );

        // Act
        $array = $data->toArray();

        // Assert
        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'phone' => '01712345678',
        ], $array);
    }

    /** @test */
    public function it_handles_null_phone_in_array_conversion(): void
    {
        // Arrange
        $data = new UserRegistrationData(
            name: 'Jane Doe',
            email: 'jane@example.com',
            password: 'password123'
        );

        // Act
        $array = $data->toArray();

        // Assert
        $this->assertEquals([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'phone' => null,
        ], $array);
    }
}
