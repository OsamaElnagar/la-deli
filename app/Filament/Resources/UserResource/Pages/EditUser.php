<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            // Actions\Action::make('impersonate')
            //     ->label('Impersonate User')
            //     ->icon('heroicon-o-identification')
            //     ->color('warning')
            //     ->requiresConfirmation()
            //     ->visible(fn () => class_exists('\Filament\Impersonate\Pages\Actions\ImpersonateAction'))
            //     ->action(function () {
            //         $impersonateAction = new \Filament\Impersonate\Pages\Actions\ImpersonateAction();
            //         $impersonateAction->record($this->getRecord())->call();
            //     }),
            Actions\Action::make('resetPassword')
                ->label('Reset Password')
                ->icon('heroicon-o-key')
                ->color('danger')
                ->form([
                    Forms\Components\TextInput::make('password')
                        ->label('New Password')
                        ->password()
                        ->required()
                        ->minLength(8)
                        ->confirmed(),
                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Confirm Password')
                        ->password()
                        ->required()
                        ->minLength(8),
                ])
                ->action(function (array $data, Actions\Action $action): void {
                    $user = $this->getRecord();
                    $user->update([
                        'password' => Hash::make($data['password']),
                    ]);

                    $action->success();
                    $this->notify('success', 'Password has been reset successfully');
                }),
           ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }
}
