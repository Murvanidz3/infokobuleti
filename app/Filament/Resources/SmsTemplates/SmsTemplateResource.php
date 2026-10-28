<?php

namespace App\Filament\Resources\SmsTemplates;

use App\Enums\Permission;
use App\Filament\NavigationGroup;
use App\Filament\Support\ChecksPermission;
use App\Filament\Support\SmsFields;
use App\Models\SmsTemplate;
use App\Support\SmsText;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Reusable texts for single sends and campaigns.
 */
class SmsTemplateResource extends Resource
{
    use ChecksPermission;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = SmsTemplate::class;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Sms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'sms/templates';

    public static function requiredPermission(): Permission
    {
        return Permission::ManageSms;
    }

    public static function getModelLabel(): string
    {
        return __('sms.templates.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('sms.templates.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('sms.fields.template_name'))->required()->maxLength(120),
            SmsFields::body(placeholders: true),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('sms.fields.template_name'))->searchable()->weight('medium'),
                TextColumn::make('body')->label(__('sms.fields.body'))->limit(80)->wrap()->searchable(),
                TextColumn::make('segments')->label(__('sms.fields.segments'))->state(fn (SmsTemplate $record): int => SmsText::segments($record->body)),
                TextColumn::make('updated_at')->label(__('sms.fields.updated_at'))->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSmsTemplates::route('/')];
    }
}
