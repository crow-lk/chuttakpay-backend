<?php

namespace App\Filament\Resources\Suppliers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid as SchemaGrid;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Schema;

class SupplierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaSection::make('Basic Information')
                    ->schema([
                        SchemaGrid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Supplier Name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('supplier_code')
                                    ->label('Supplier Code')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->placeholder('e.g., SUP-001'),
                            ]),

                        SchemaGrid::make(2)
                            ->schema([
                                TextInput::make('contact_person')
                                    ->label('Contact Person')
                                    ->maxLength(255),

                                Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                    ])
                                    ->default('active')
                                    ->required(),
                            ]),
                    ]),

                SchemaSection::make('Contact Information')
                    ->schema([
                        SchemaGrid::make(2)
                            ->schema([
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),

                                TextInput::make('phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(255),
                            ]),

                        Textarea::make('address')
                            ->label('Address')
                            ->rows(3)
                            ->columnSpanFull(),

                        SchemaGrid::make(3)
                            ->schema([
                                TextInput::make('city')
                                    ->label('City')
                                    ->maxLength(255),

                                TextInput::make('state')
                                    ->label('State/Province')
                                    ->maxLength(255),

                                TextInput::make('postal_code')
                                    ->label('Postal Code')
                                    ->maxLength(255),
                            ]),

                        TextInput::make('country')
                            ->label('Country')
                            ->maxLength(255),
                    ]),

                SchemaSection::make('Business Information')
                    ->schema([
                        TextInput::make('tax_number')
                            ->label('Tax Number')
                            ->maxLength(255)
                            ->placeholder('e.g., VAT, GST, or Tax ID'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->placeholder('Additional notes about this supplier...'),
                    ])
                    ->collapsible(),
            ]);
    }
}
