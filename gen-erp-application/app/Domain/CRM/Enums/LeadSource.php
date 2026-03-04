<?php

namespace App\Domain\CRM\Enums;

enum LeadSource: string
{
    case WEBSITE = 'website';
    case REFERRAL = 'referral';
    case SOCIAL_MEDIA = 'social_media';
    case ADVERTISEMENT = 'advertisement';
    case EMAIL_CAMPAIGN = 'email_campaign';
    case COLD_CALL = 'cold_call';
    case TRADE_SHOW = 'trade_show';
    case PARTNER = 'partner';
    case ORGANIC_SEARCH = 'organic_search';
    case PAID_SEARCH = 'paid_search';
    case DIRECT = 'direct';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::WEBSITE => 'Website',
            self::REFERRAL => 'Referral',
            self::SOCIAL_MEDIA => 'Social Media',
            self::ADVERTISEMENT => 'Advertisement',
            self::EMAIL_CAMPAIGN => 'Email Campaign',
            self::COLD_CALL => 'Cold Call',
            self::TRADE_SHOW => 'Trade Show',
            self::PARTNER => 'Partner',
            self::ORGANIC_SEARCH => 'Organic Search',
            self::PAID_SEARCH => 'Paid Search',
            self::DIRECT => 'Direct',
            self::OTHER => 'Other',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::WEBSITE => 'globe-alt',
            self::REFERRAL => 'user-group',
            self::SOCIAL_MEDIA => 'share',
            self::ADVERTISEMENT => 'speakerphone',
            self::EMAIL_CAMPAIGN => 'mail',
            self::COLD_CALL => 'phone',
            self::TRADE_SHOW => 'office-building',
            self::PARTNER => 'handshake',
            self::ORGANIC_SEARCH => 'search',
            self::PAID_SEARCH => 'currency-dollar',
            self::DIRECT => 'arrow-right',
            self::OTHER => 'dots-horizontal',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::WEBSITE => 'Lead came through website contact form or inquiry',
            self::REFERRAL => 'Lead was referred by existing customer or contact',
            self::SOCIAL_MEDIA => 'Lead came from social media platforms',
            self::ADVERTISEMENT => 'Lead came from advertising campaigns',
            self::EMAIL_CAMPAIGN => 'Lead came from email marketing campaigns',
            self::COLD_CALL => 'Lead generated through cold calling',
            self::TRADE_SHOW => 'Lead met at trade shows or events',
            self::PARTNER => 'Lead came through business partners',
            self::ORGANIC_SEARCH => 'Lead found us through organic search',
            self::PAID_SEARCH => 'Lead came from paid search advertising',
            self::DIRECT => 'Lead contacted us directly',
            self::OTHER => 'Lead source not specified or other',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())->map(fn($source) => [
            'value' => $source->value,
            'label' => $source->label(),
            'icon' => $source->icon(),
            'description' => $source->description(),
        ])->toArray();
    }
}