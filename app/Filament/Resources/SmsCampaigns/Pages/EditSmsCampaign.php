<?php

namespace App\Filament\Resources\SmsCampaigns\Pages;

use App\Filament\Resources\SmsCampaigns\SmsCampaignResource;
use App\Models\SmsCampaign;
use App\Services\Sms\Campaigns;
use Filament\Resources\Pages\EditRecord;

/**
 * Drafts and scheduled campaigns only; saving checks the recipients again.
 */
class EditSmsCampaign extends EditRecord
{
    protected static string $resource = SmsCampaignResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var SmsCampaign $campaign */
        $campaign = $this->getRecord();

        if (! $campaign->status->isEditable()) {
            $this->redirect(SmsCampaignResource::getUrl('view', ['record' => $campaign]));
        }
    }

    public function getTitle(): string
    {
        /** @var SmsCampaign $campaign */
        $campaign = $this->getRecord();

        return $campaign->name;
    }

    protected function afterSave(): void
    {
        /** @var SmsCampaign $campaign */
        $campaign = $this->getRecord();

        if ($campaign->status->isEditable()) {
            app(Campaigns::class)->prepare($campaign);
        }
    }

    protected function getRedirectUrl(): string
    {
        return SmsCampaignResource::getUrl('view', ['record' => $this->getRecord()]);
    }
}
