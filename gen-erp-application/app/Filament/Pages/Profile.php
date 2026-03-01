<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * User profile page with personal info and security settings.
 */
class Profile extends BaseEditProfile
{
    protected static string $view = 'filament.pages.profile';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('profile')
                    ->tabs([
                        $this->personalInfoTab(),
                        $this->securityTab(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function personalInfoTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('Personal Information'))
            ->icon('heroicon-o-user')
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label(__('Full Name'))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label(__('Email'))
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            TextInput::make('phone')
                                ->label(__('Phone'))
                                ->tel()
                                ->maxLength(20),
                            Select::make('preferred_locale')
                                ->label(__('Preferred Language'))
                                ->options([
                                    'en' => 'English',
                                    'bn' => 'বাংলা',
                                ])
                                ->default('en')
                                ->required(),
                            FileUpload::make('avatar_url')
                                ->label(__('Profile Picture'))
                                ->image()
                                ->avatar()
                                ->imageEditor()
                                ->maxSize(2048)
                                ->directory('avatars')
                                ->columnSpanFull(),
                        ]),
                    ]),
            ]);
    }

    protected function securityTab(): Tabs\Tab
    {
        return Tabs\Tab::make(__('Security'))
            ->icon('heroicon-o-lock-closed')
            ->schema([
                Section::make(__('Change Password'))
                    ->description(__('Update your password to keep your account secure.'))
                    ->schema([
                        Grid::make(1)->schema([
                            TextInput::make('current_password')
                                ->label(__('Current Password'))
                                ->password()
                                ->revealable()
                                ->currentPassword()
                                ->dehydrated(false),
                            TextInput::make('password')
                                ->label(__('New Password'))
                                ->password()
                                ->revealable()
                                ->rule(Password::default())
                                ->dehydrated(fn ($state) => filled($state))
                                ->confirmed(),
                            TextInput::make('password_confirmation')
                                ->label(__('Confirm New Password'))
                                ->password()
                                ->revealable()
                                ->dehydrated(false),
                        ]),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Only hash password if it's being changed
        if (filled($data['password'])) {
            $data['password'] = Hash::make($data['password']);
            $data['password_changed_at'] = now();
        } else {
            unset($data['password']);
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('Profile updated'))
            ->body(__('Your profile has been updated successfully.'));
    }
}
