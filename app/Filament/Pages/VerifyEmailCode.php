<?php

namespace App\Filament\Pages;

use App\Notifications\SendEmailVerificationCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class VerifyEmailCode extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.verify-email-code';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->redirect('/admin/login');
            return;
        }

        if ($user->email_verified_at) {
            $this->redirect('/admin');
            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->label('Verification Code')
                    ->placeholder('Enter 6-digit code')
                    ->required()
                    ->numeric()
                    ->minLength(6)
                    ->maxLength(6),
            ])
            ->statePath('data');
    }

    public function verify(): void
    {
        $user = Auth::user();
        $code = $this->data['code'] ?? null;

        if (! $user) {
            $this->redirect('/admin/login');
            return;
        }

        if ($user->email_verified_at) {
            $this->redirect('/admin');
            return;
        }

        if (! $user->email_verification_code || ! $user->email_verification_code_expires_at) {
            Notification::make()
                ->title('No verification code found.')
                ->body('Please click Send OTP Again to receive a new code.')
                ->danger()
                ->send();

            return;
        }

        if (now()->greaterThan($user->email_verification_code_expires_at)) {
            Notification::make()
                ->title('Verification code expired.')
                ->body('Please click Send OTP Again to receive a new code.')
                ->danger()
                ->send();

            return;
        }

        if ($user->email_verification_code !== $code) {
            Notification::make()
                ->title('Invalid verification code.')
                ->body('Please check your email and try again.')
                ->danger()
                ->send();

            return;
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ])->save();

        Notification::make()
            ->title('Email verified successfully.')
            ->body('Your account is now waiting for Super Admin approval.')
            ->success()
            ->send();

        $this->redirect('/admin');
    }

    public function resendCode(): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->redirect('/admin/login');
            return;
        }

        if ($user->email_verified_at) {
            $this->redirect('/admin');
            return;
        }

        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'email_verification_code' => $code,
            'email_verification_code_expires_at' => now()->addMinutes(10),
        ])->saveQuietly();

        $user->notify(new SendEmailVerificationCode($code));

        $this->form->fill([
            'code' => '',
        ]);

        Notification::make()
            ->title('New OTP sent.')
            ->body('Please check your Gmail inbox or spam folder.')
            ->success()
            ->send();
    }
}