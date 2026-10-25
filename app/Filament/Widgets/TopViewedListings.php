<?php

namespace App\Filament\Widgets;

use App\Enums\ListingStatus;
use App\Models\AdminListing;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopViewedListings extends TableWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 1];

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('admin.dashboard.top_viewed'))
            ->query(AdminListing::query()->where('status', ListingStatus::Published->value)->orderByDesc('views_count')->limit(8))
            ->columns([
                TextColumn::make('title')->label(__('admin.fields.title'))->getStateUsing(fn (AdminListing $record): string => $record->titleText())->limit(36)
                    ->description(fn (AdminListing $record): string => __('admin.morph.'.$record->listing_type)),
                TextColumn::make('views_count')->label(__('admin.fields.views_count'))->numeric()->alignEnd(),
            ])
            ->paginated(false);
    }
}
