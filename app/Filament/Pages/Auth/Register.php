<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Pages\VerifyEmailCode;
use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;

class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getFirstNameFormComponent(),
                        $this->getMiddleNameFormComponent(),
                        $this->getLastNameFormComponent(),
                        $this->getBirthdateFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getFirstNameFormComponent(): Component
    {
        return TextInput::make('first_name')
            ->label('First name')
            ->required()
            ->maxLength(255)
            ->autocomplete('given-name');
    }

    protected function getMiddleNameFormComponent(): Component
    {
        return TextInput::make('middle_name')
            ->label('Middle name')
            ->placeholder('Optional')
            ->maxLength(255)
            ->autocomplete('additional-name');
    }

    protected function getLastNameFormComponent(): Component
    {
        return TextInput::make('last_name')
            ->label('Last name')
            ->required()
            ->maxLength(255)
            ->autocomplete('family-name')
            ->rules([
                function (Get $get) {
                    return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                        $firstName = trim((string) $get('first_name'));
                        $middleName = trim((string) $get('middle_name'));
                        $lastName = trim((string) $get('last_name'));

                        $fullName = collect([
                            $firstName,
                            $middleName,
                            $lastName,
                        ])
                            ->filter()
                            ->implode(' ');

                        if ($fullName === '') {
                            return;
                        }

                        $exists = User::query()
                            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($fullName)])
                            ->exists();

                        if ($exists) {
                            $fail('This full name already exists.');
                        }
                    };
                },
            ]);
    }

    protected function getBirthdateFormComponent(): Component
    {
        return DatePicker::make('birthdate')
            ->label('Birthdate')
            ->required()
            ->native(false)
            ->displayFormat('F d, Y')
            ->maxDate(now());
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Email address')
            ->placeholder('example@gmail.com or example@dict.gov.ph')
            ->email()
            ->required()
            ->maxLength(255)
            ->unique(User::class, 'email')
            ->rules([
                'regex:/^[A-Za-z0-9._%+\-]+@(gmail\.com|dict\.gov\.ph)$/i',
            ])
            ->validationMessages([
                'unique' => 'This email address is already registered.',
                'regex' => 'Only Gmail or DICT official email addresses are allowed.',
            ])
            ->autocomplete('email');
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->required()
            ->minLength(8)
            ->same('passwordConfirmation')
            ->validationMessages([
                'same' => 'The password confirmation does not match.',
            ])
            ->revealable()
            ->autocomplete('new-password');
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label('Confirm password')
            ->password()
            ->required()
            ->revealable()
            ->autocomplete('new-password')
            ->dehydrated(false);
    }

    protected function mutateFormDataBeforeRegister(array $data): array
    {
        $firstName = trim($data['first_name']);
        $middleName = ! empty($data['middle_name']) ? trim($data['middle_name']) : null;
        $lastName = trim($data['last_name']);

        $data['first_name'] = $firstName;
        $data['middle_name'] = $middleName;
        $data['last_name'] = $lastName;

        $data['name'] = collect([
            $firstName,
            $middleName,
            $lastName,
        ])
            ->filter()
            ->implode(' ');

        $data['email'] = strtolower(trim($data['email']));
        $data['password'] = Hash::make($data['password']);

        // New users stay pending until Super Admin approval.
        $data['is_active'] = false;

        unset($data['passwordConfirmation']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return VerifyEmailCode::getUrl();
    }
}