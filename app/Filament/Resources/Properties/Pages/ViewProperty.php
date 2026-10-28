<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Support\ListingAdmin;
use App\Models\Property;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewProperty extends ViewRecord
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ListingAdmin::approveAction(),
            ListingAdmin::rejectAction(),
            ListingAdmin::changeStatusAction(),
            self::ownerVerifiedAction(),
            EditAction::make(),
        ];
    }

    /**
     * Trust layer: staff checked the ownership documents (extract from the public registry,
     * ID of the owner) and mark the property "owner verified".
     */
    public static function ownerVerifiedAction(): Action
    {
        return Action::make('ownerVerified')
            ->label(fn (Property $record): string => $record->owner_verified_at !== null ? __('admin.properties.owner_unverify') : __('admin.properties.owner_verify'))
            ->icon(fn (Property $record) => $record->owner_verified_at !== null ? Heroicon::OutlinedXMark : Heroicon::OutlinedShieldCheck)
            ->color(fn (Property $record): string => $record->owner_verified_at !== null ? 'gray' : 'success')
            ->modalDescription(__('admin.properties.owner_verify_help'))
            ->requiresConfirmation()
            ->action(function (Property $record): void {
                $verified = $record->owner_verified_at === null;
                $record->forceFill([
                    'owner_verified_at' => $verified ? now() : null,
                    'owner_verified_by' => $verified ? auth()->id() : null,
                ])->save();

                activity('moderation')->performedOn($record)->causedBy(auth()->user())->event($verified ? 'owner_verified' : 'owner_unverified')->log('owner verification changed');

                Notification::make()->success()->title(__('admin.saved'))->send();
            });
    }
}
