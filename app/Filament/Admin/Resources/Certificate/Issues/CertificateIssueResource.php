<?php

namespace App\Filament\Admin\Resources\Certificate\Issues;

use App\Domain\Certificate\Actions\IssueCertificate;
use App\Domain\Certificate\Enums\CertificateIssueStatus;
use App\Filament\Admin\NavigationGroup;
use App\Filament\Admin\Resources\Certificate\Issues\Pages\ListCertificateIssues;
use App\Filament\Admin\Support\ShowsActionErrors;
use App\Models\Certificate\CertificateIssue;
use App\Models\Certificate\CertificateType;
use App\Models\Person\Person;
use App\Models\Person\StudentRecord;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * The issue ledger (legacy SRCERT: no issuer, no status, hard deletes). Every issue stays;
 * revocation keeps the row and the file and makes the verification page say so.
 */
class CertificateIssueResource extends Resource
{
    protected static ?string $model = CertificateIssue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = NavigationGroup::Certificates;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'certificates/issues';

    public static function getModelLabel(): string
    {
        return __('certificate.resources.issues.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('certificate.resources.issues.plural_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('studentRecord', fn (Builder $record) => StudentRecord::constrainVisibleTo($record, self::actor()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['studentRecord.person', 'type', 'issuedBy']))
            ->columns([
                TextColumn::make('issued_at')->label(__('certificate.fields.issued_at'))->dateTime()->sortable(),
                TextColumn::make('serial_number')->label(__('certificate.fields.serial_number'))->searchable()->copyable(),
                TextColumn::make('type.name')->label(__('certificate.fields.type'))->description(fn (CertificateIssue $record) => $record->locale),
                TextColumn::make('studentRecord.student_number')->label(__('people.fields.student_number'))->searchable(),
                TextColumn::make('name')->label(__('people.fields.name'))->state(fn (CertificateIssue $record) => $record->studentRecord->person->displayName())
                    ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('studentRecord.person', fn (Builder $person) => Person::constrainToSearch($person, $search))),
                TextColumn::make('purpose')->label(__('certificate.fields.purpose'))->placeholder('-')->limit(30),
                TextColumn::make('copies')->label(__('certificate.fields.copies')),
                TextColumn::make('status')->label(__('registration.fields.status'))->formatStateUsing(fn (CertificateIssueStatus $state) => $state->label())->badge()
                    ->color(fn (CertificateIssueStatus $state) => $state === CertificateIssueStatus::Issued ? 'success' : 'danger'),
                TextColumn::make('issuedBy.email')->label(__('certificate.fields.issued_by'))->placeholder('-')->toggleable(),
            ])
            ->defaultSort('issued_at', 'desc')
            ->filters([
                SelectFilter::make('certificate_type_id')->label(__('certificate.fields.type'))
                    ->options(fn () => CertificateType::query()->orderBy('sort_order')->get()->mapWithKeys(fn (CertificateType $type) => [$type->id => $type->name])),
                SelectFilter::make('status')->label(__('registration.fields.status'))
                    ->options(collect(CertificateIssueStatus::cases())->mapWithKeys(fn (CertificateIssueStatus $status) => [$status->value => $status->label()])),
            ])
            ->recordActions([
                Action::make('download')->label(__('certificate.actions.download'))->icon(Heroicon::OutlinedArrowDownTray)->color('gray')
                    ->url(fn (CertificateIssue $record) => route('certificates.download', $record))->openUrlInNewTab(),
                Action::make('verify')->label(__('certificate.actions.verify_page'))->icon(Heroicon::OutlinedQrCode)->color('gray')
                    ->url(fn (CertificateIssue $record) => route('certificates.verify', $record->verification_code))->openUrlInNewTab(),
                Action::make('rerender')->label(__('certificate.actions.rerender'))->icon(Heroicon::OutlinedArrowPath)->color('gray')
                    ->visible(fn (CertificateIssue $record) => $record->status === CertificateIssueStatus::Issued)
                    ->requiresConfirmation()->modalDescription(__('certificate.help.rerender'))
                    ->action(fn (CertificateIssue $record) => ShowsActionErrors::run(fn () => app(IssueCertificate::class)->rerender($record, self::actor()))),
                Action::make('revoke')->label(__('certificate.actions.revoke'))->icon(Heroicon::OutlinedNoSymbol)->color('danger')
                    ->visible(fn (CertificateIssue $record) => $record->status === CertificateIssueStatus::Issued)
                    ->schema([Textarea::make('reason')->label(__('common.reason'))->required()->maxLength(1000)])
                    ->action(fn (CertificateIssue $record, array $data) => ShowsActionErrors::run(fn () => app(IssueCertificate::class)->revoke($record, self::actor(), $data['reason']))),
            ]);
    }

    public static function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    public static function getPages(): array
    {
        return ['index' => ListCertificateIssues::route('/')];
    }
}
