<?php

namespace App\Filament\Pages;

use App\Enums\CompanyRole;
use App\Models\CompanyUser;
use App\Models\Invitation;
use App\Services\CompanyContext;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Team management page: members list, role editing, and invitation management.
 */
class TeamSettings extends Page implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Team';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.team-settings';

    // Invite form
    public string $invite_email = '';

    public string $invite_role = '';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CompanyUser::query()
                    ->where('company_id', CompanyContext::activeId())
                    ->with('user')
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label(__('Email'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('Role'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof \App\Enums\CompanyRole ? $state->label() : (CompanyRole::tryFrom($state)?->label() ?? $state))
                    ->sortable(),
                TextColumn::make('joined_at')
                    ->label(__('Joined'))
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? __('Active') : __('Inactive'))
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
            ])
            ->actions([
                Action::make('change_role')
                    ->label(__('Change Role'))
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Select::make('role')
                            ->label(__('Role'))
                            ->options(CompanyRole::options())
                            ->required(),
                    ])
                    ->action(function (CompanyUser $record, array $data): void {
                        if ($record->is_owner) {
                            Notification::make()
                                ->title(__('Cannot change the owner\'s role.'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update(['role' => $data['role']]);

                        Notification::make()
                            ->title(__('Role updated.'))
                            ->success()
                            ->send();
                    })
                    ->hidden(fn (CompanyUser $record): bool => $record->is_owner),
                Action::make('remove')
                    ->label(__('Remove'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (CompanyUser $record): void {
                        if ($record->is_owner) {
                            Notification::make()
                                ->title(__('Cannot remove the company owner.'))
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->title(__('Member removed.'))
                            ->success()
                            ->send();
                    })
                    ->hidden(fn (CompanyUser $record): bool => $record->is_owner),
            ]);
    }

    public function inviteForm(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('invite_email')
                    ->label(__('Email'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                Select::make('invite_role')
                    ->label(__('Role'))
                    ->options(
                        collect(CompanyRole::options())->except(CompanyRole::OWNER->value)->all()
                    )
                    ->required(),
            ])
            ->statePath(null);
    }

    public function sendInvitation(): void
    {
        $this->validate([
            'invite_email' => ['required', 'email', 'max:255'],
            'invite_role' => ['required', 'string'],
        ]);

        $company = CompanyContext::active();

        // Check if already a member
        $alreadyMember = $company->users()
            ->where('email', $this->invite_email)
            ->exists();

        if ($alreadyMember) {
            Notification::make()
                ->title(__('This user is already a member of this company.'))
                ->warning()
                ->send();

            return;
        }

        // Check for existing pending invitation
        $existingInvite = Invitation::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('email', $this->invite_email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();

        if ($existingInvite) {
            Notification::make()
                ->title(__('A pending invitation already exists for this email.'))
                ->warning()
                ->send();

            return;
        }

        $invitation = Invitation::create([
            'uuid' => Str::uuid()->toString(),
            'company_id' => $company->id,
            'email' => $this->invite_email,
            'role' => $this->invite_role,
            'invited_by' => auth()->id(),
            'token' => Str::random(64),
            'expires_at' => now()->addHours(72),
        ]);

        // Dispatch email job
        \App\Jobs\SendInvitationEmail::dispatch($invitation);

        $this->reset(['invite_email', 'invite_role']);

        Notification::make()
            ->title(__('Invitation sent.'))
            ->success()
            ->send();
    }

    /**
     * Get pending invitations for the active company.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Invitation>
     */
    public function getPendingInvitationsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return Invitation::withoutGlobalScopes()
            ->where('company_id', CompanyContext::activeId())
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with('invitedBy')
            ->orderByDesc('created_at')
            ->get();
    }
}
