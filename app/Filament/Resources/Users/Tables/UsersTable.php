<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('عضویت')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('نام')
                    ->searchable(['first_name', 'last_name', 'name'])
                    ->placeholder('—'),

                TextColumn::make('mobile')
                    ->label('موبایل')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('account_type')
                    ->label('نوع')
                    ->badge()
                    ->color(fn (string $state) => $state === 'legal' ? 'info' : 'gray')
                    ->formatStateUsing(fn (string $state) => User::ACCOUNT_TYPES[$state] ?? $state),

                TextColumn::make('company')
                    ->label('شرکت')
                    ->searchable(['company', 'company_national_id'])
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'awaiting_approval' => 'info',
                        'suspended' => 'warning',
                        'blocked' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => User::STATUSES[$state] ?? $state),

                TextColumn::make('documents_status')
                    ->label('مدارک')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => User::DOCUMENT_STATUSES[$state] ?? $state),

                TextColumn::make('plan.name')
                    ->label('پلن')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('userGroup.name')
                    ->label('گروه کاربری')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),

                IconColumn::make('profile_completed_at')
                    ->label('اطلاعات')
                    ->boolean()
                    ->getStateUsing(fn (User $record) => $record->profile_completed_at !== null),

                TextColumn::make('last_login_at')
                    ->label('آخرین ورود')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->label('نوع حساب')
                    ->options(User::ACCOUNT_TYPES),

                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(User::STATUSES),

                SelectFilter::make('documents_status')
                    ->label('وضعیت مدارک')
                    ->options(User::DOCUMENT_STATUSES),

                SelectFilter::make('plan_id')
                    ->label('پلن')
                    ->relationship('plan', 'name'),

                SelectFilter::make('user_group_id')
                    ->label('گروه کاربری')
                    ->relationship('userGroup', 'name'),

                TernaryFilter::make('profile_completed_at')
                    ->label('تکمیل اطلاعات')
                    ->nullable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
