<?php

namespace App\Domain\Auth\DTOs;

readonly class UpdateUserData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $phone = null,
        public ?string $avatarUrl = null,
        public ?string $preferredLocale = null,
    ) {}

    /**
     * Create from Form Request.
     */
    public static function fromRequest(\Illuminate\Http\Request $request): self
    {
        return new self(
            name: $request->has('name') ? $request->string('name') : null,
            email: $request->has('email') ? $request->string('email') : null,
            password: $request->has('password') ? $request->string('password') : null,
            phone: $request->has('phone') ? $request->string('phone') : null,
            avatarUrl: $request->has('avatar_url') ? $request->string('avatar_url') : null,
            preferredLocale: $request->has('preferred_locale') ? $request->string('preferred_locale') : null,
        );
    }

    /**
     * Convert to array for model update (only non-null values).
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->email !== null) {
            $data['email'] = $this->email;
        }

        if ($this->password !== null) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($this->password);
        }

        if ($this->phone !== null) {
            $data['phone'] = $this->phone;
        }

        if ($this->avatarUrl !== null) {
            $data['avatar_url'] = $this->avatarUrl;
        }

        if ($this->preferredLocale !== null) {
            $data['preferred_locale'] = $this->preferredLocale;
        }

        return $data;
    }
}