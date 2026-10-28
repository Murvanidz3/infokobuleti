<?php

namespace App\Filament\Resources\SmsCampaigns\Pages;

use App\Enums\SmsCampaignStatus;
use App\Filament\Resources\SmsCampaigns\SmsCampaignResource;
use App\Models\SmsCampaign;
use App\Services\Sms\Campaigns;
use Filament\Resources\Pages\CreateRecord;

/**
 * Saves a draft; the recipients are resolved and checked right after, then the view page shows
 * the check and the send buttons.
 */
class CreateSmsCampaign extends CreateRecord
{
    protected static string $resource = SmsCampaignResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return __('sms.actions.new_campaign');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [
            ...$data,
            'status' => SmsCampaignStatus::Draft,
            'created_by' => auth()->id(),
        ];
    }

    protected function afterCreate(): void
    {
        /** @var SmsCampaign $campaign */
        $campaign = $this->getRecord();
        app(Campaigns::class)->prepare($campaign);
    }

    protected function getRedirectUrl(): string
    {
        return SmsCampaignResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
