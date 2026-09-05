<?php

namespace App\Filament\Resources\RepresentationApplications\Schemas;

use App\Models\RepresentationApplication;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RepresentationApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بررسی درخواست')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label('وضعیت')
                            ->options(RepresentationApplication::STATUSES)
                            ->required(),

                        Textarea::make('admin_note')
                            ->label('یادداشت داخلی')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('اطلاعات متقاضی')
                    ->columns(2)
                    ->schema([
                        TextInput::make('full_name')
                            ->label('نام و نام‌خانوادگی')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('mobile')
                            ->label('موبایل')
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('email')
                            ->label('ایمیل')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),

                        TextInput::make('city')
                            ->label('شهر')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),

                        TextInput::make('company_name')
                            ->label('نام شرکت')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),

                        TextInput::make('tier.name')
                            ->label('تعرفه مدنظر')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),

                        Textarea::make('message')
                            ->label('توضیحات متقاضی')
                            ->disabled()
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
