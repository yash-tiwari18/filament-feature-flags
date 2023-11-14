<?php

namespace Stephenjude\FeaturePlugin\Resources;

use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Laravel\Pennant\Feature;
use Laravel\Pennant\FeatureManager;
use Stephenjude\Events\FeatureActivatedForAll;
use Stephenjude\FeaturePlugin\Events\FeatureDeactivatedForAll;
use Stephenjude\FeaturePlugin\Events\FeatureSegmentCreated;
use Stephenjude\FeaturePlugin\Models\FeatureSegment;

class ManageFeatureSegments extends ManageRecords
{
    protected static string $resource = FeatureSegmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalWidth('md')
                ->modalHeading('Create Feature Segment')
                ->label('Segment Feature')
                ->after(fn (FeatureSegment $record) => $this->afterCreate($record)),

            Actions\Action::make('activate_for_all')
                ->label('Activate For All')
                ->modalWidth('md')
                ->modalDescription(fn ($record) => 'This action will activate the selected feature for all users.')
                ->form([
                    Select::make('feature')
                        ->required()
                        ->options(FeatureSegment::featureOptionsList())
                        ->columnSpanFull(),
                ])
                ->modalSubmitActionLabel('Activate')
                ->action(fn ($data) => $this->activateForAll($data['feature'])),

            Actions\Action::make('deactivate_for_all')
                ->modalWidth('md')
                ->label('Deactivate For All')
                ->modalDescription(fn ($record) => 'This action will deactivate this feature for all users.')
                ->form([
                    Select::make('feature')
                        ->required()
                        ->options(FeatureSegment::featureOptionsList())
                        ->columnSpanFull(),
                ])
                ->modalSubmitActionLabel('Deactivate')
                ->color('danger')
                ->action(fn ($data) => $this->deactivateForAll($data['feature'])),
        ];
    }

    private function activateForAll(string $feature): void
    {
        app(FeatureManager::class)->store()->purge($feature);

        Feature::activateForEveryone($feature);

        Notification::make()->success()->title('Done!')->body("{$feature::title()} activated for all users.")->send();

        FeatureActivatedForAll::dispatch($feature);
    }

    private function deactivateForAll(string $feature): void
    {
        app(FeatureManager::class)->store()->purge($feature);

        Feature::deactivateForEveryone($feature);

        Notification::make()->success()->title('Done!')->body("{$feature::title()} deactivated for all users.")->send();

        FeatureDeactivatedForAll::dispatch($feature);
    }

    private function afterCreate(FeatureSegment $featureSegment): void
    {
        app(FeatureManager::class)->store()->purge($featureSegment->feature);

        FeatureSegmentCreated::dispatch($featureSegment);
    }
}
