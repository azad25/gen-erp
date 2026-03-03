<?php

namespace App\Domain\Auth\DTOs;

readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone = null,
        public ?string $avatarUrl = null,
        public ?string $preferredLocale = null,
        public bool $isSuperadmin = false,
    ) {}

    /**
     * Create from Form Request.
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            name: $request->string('name'),
            email: $request->string('email'),
            password: $request->string('password'),
            phone: $request->string('phone'),
            avatarUrl: $request->string('avatar_url'),
            preferredLocale: $request->string('preferred_locale', 'en'),
            isSuperadmin: $request->boolean('is_superadmin', false),
        );
    }

    /**
     * Convert to array for model creation.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => \Illuminate\Support\Facades\Hash::make($this->password),
            'phone' => $this->phone,
            'avatar_url' => $this->avatarUrl,
            'preferred_locale' => $this->preferredLocale,
            'is_superadmin' => $this->isSuperadmin,
        ];
    }
}