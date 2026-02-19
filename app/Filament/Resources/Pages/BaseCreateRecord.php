<?php

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;

abstract class BaseCreateRecord extends CreateRecord
{
    public function defaultForm(Schema $schema): Schema
    {
        return parent::defaultForm($schema)->columns(1);
    }
}
