<?php

namespace Database\Factories\Domain\Notification;

use App\Domain\Auth\Models\User;
use App\Domain\Notification\Models\ErpNotification;
use App\Domain\Auth\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ErpNotificationFactory extends Factory
{
    protected $model = ErpNotification::class;

    public function definition(): array
    {
        $domains = ['system', 'invoice', 'hr', 'inventory', 'crm', 'logistics'];
        $types = ['created', 'updated', 'deleted', 'alert', 'reminder'];
        $colors = ['info', 'success', 'warning', 'danger'];
        $icons = ['info-circle', 'check-circle', 'exclamation-triangle', 'x-circle'];

        $domain = $this->faker->randomElement($domains);
        $type = $this->faker->randomElement($types);
        $color = $this->faker->randomElement($colors);

        return [
            'id' => (string) Str::uuid(),
            'tenant_id' => Company::factory(),
            'user_id' => User::factory(),
            'domain' => $domain,
            'type' => "{$domain}.{$type}",
            'title_key' => "notifications.{$domain}.{$type}.title",
            'body_key' => "notifications.{$domain}.{$type}.body",
            'translation_params' => $this->faker->optional(0.8)->passthrough([
                'name' => $this->faker->name,
                'amount' => $this->faker->numberBetween(100, 10000),
                'date' => $this->faker->date(),
            ]),
            'icon' => $this->faker->randomElement($icons),
            'color' => $color,
            'action_url' => $this->faker->optional()->url,
            'action_label_key' => $this->faker->optional()->randomElement([
                'notifications.actions.view',
                'notifications.actions.edit',
                'notifications.actions.approve',
            ]),
            'meta' => [
                'source' => $this->faker->randomElement(['system', 'user', 'api']),
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            ],
            'read_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 week', 'now'),
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => $this->faker->dateTimeBetween($attributes['created_at'] ?? '-1 week', 'now'),
        ]);
    }

    public function systemAlert(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => 'system',
            'type' => 'system.alert',
            'title_key' => 'notifications.system.alert.title',
            'body_key' => 'notifications.system.alert.body',
            'icon' => 'info-circle',
            'color' => 'info',
        ]);
    }

    public function invoicePaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => 'invoice',
            'type' => 'invoice.paid',
            'title_key' => 'notifications.invoice.paid.title',
            'body_key' => 'notifications.invoice.paid.body',
            'icon' => 'check-circle',
            'color' => 'success',
            'translation_params' => [
                'number' => 'INV-' . $this->faker->numberBetween(1000, 9999),
                'amount' => '৳' . number_format($this->faker->numberBetween(1000, 50000)),
            ],
        ]);
    }

    public function shipmentDelivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => 'logistics',
            'type' => 'shipment.delivered',
            'title_key' => 'notifications.logistics.shipment_delivered.title',
            'body_key' => 'notifications.logistics.shipment_delivered.body',
            'icon' => 'truck',
            'color' => 'success',
            'translation_params' => [
                'tracking' => 'LOG-' . $this->faker->numberBetween(100000, 999999),
                'customer' => $this->faker->name,
            ],
        ]);
    }

    public function leadCreated(): static
    {
        return $this->state(fn (array $attributes) => [
            'domain' => 'crm',
            'type' => 'lead.created',
            'title_key' => 'notifications.crm.lead_created.title',
            'body_key' => 'notifications.crm.lead_created.body',
            'icon' => 'user-plus',
            'color' => 'info',
            'translation_params' => [
                'name' => $this->faker->name,
                'company' => $this->faker->company,
            ],
        ]);
    }
}