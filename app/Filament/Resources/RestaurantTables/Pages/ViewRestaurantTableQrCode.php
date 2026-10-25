<?php

namespace App\Filament\Resources\RestaurantTables\Pages;

use App\Filament\Resources\RestaurantTables\RestaurantTableResource;
use App\Models\RestaurantTable;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Filament\Resources\Pages\ViewRecord;

class ViewRestaurantTableQrCode extends ViewRecord
{
    protected static string $resource = RestaurantTableResource::class;

    protected string $view = 'filament.resources.restaurant-tables.pages.qr-code';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        abort_unless($this->getTable()->is_active, 404);
    }

    public function getTable(): RestaurantTable
    {
        /** @var RestaurantTable $table */
        $table = $this->getRecord();

        return $table;
    }

    public function getPublicMenuUrl(): string
    {
        $table = $this->getTable();

        return route('public.menu.table', [
            'restaurant' => $table->restaurant->slug,
            'uuid' => $table->uuid,
        ]);
    }

    public function getQrCodeSvg(): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
        ]);

        return (string) (new QRCode($options))->render($this->getPublicMenuUrl());
    }
}
