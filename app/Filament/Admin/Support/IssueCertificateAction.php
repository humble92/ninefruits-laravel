<?php

namespace App\Filament\Admin\Support;

use App\Domain\Certificate\Actions\IssueCertificate;
use App\Domain\Institution\Settings\InstitutionSettings;
use App\Domain\Security\Enums\Permission;
use App\Models\Certificate\CertificateType;
use App\Models\Person\StudentRecord;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Js;
use Livewire\Component;

/**
 * "Issue a certificate" on a student record: kind, language, purpose and copies; the PDF
 * opens when done.
 */
final class IssueCertificateAction
{
    public static function make(): Action
    {
        return Action::make('issueCertificate')->label(__('certificate.actions.issue'))->icon(Heroicon::OutlinedDocumentPlus)
            ->authorize(fn () => (bool) Auth::user()?->can(Permission::CertificatesIssue->value))
            ->schema([
                Select::make('certificate_type_id')->label(__('certificate.fields.type'))->required()
                    ->options(fn () => CertificateType::query()->where('is_active', true)->orderBy('sort_order')->get()->mapWithKeys(fn (CertificateType $type) => [$type->id => $type->name])),
                Select::make('locale')->label(__('common.language'))->required()->default(fn () => app()->getLocale())
                    ->options(fn () => collect(app(InstitutionSettings::class)->supported_locales)->mapWithKeys(fn (string $locale) => [$locale => $locale])),
                TextInput::make('purpose')->label(__('certificate.fields.purpose'))->maxLength(200),
                TextInput::make('copies')->label(__('certificate.fields.copies'))->integer()->minValue(1)->maxValue(20)->default(1)->required(),
            ])
            ->action(function (StudentRecord $record, array $data, Action $action, Component $livewire) {
                /** @var User $user */
                $user = Auth::user();
                $issue = ShowsActionErrors::run(fn () => app(IssueCertificate::class)->handle(
                    $record, CertificateType::query()->findOrFail($data['certificate_type_id']), (string) $data['locale'], $user, $data['purpose'] ?? null, (int) $data['copies'],
                ));
                $action->successNotificationTitle(__('certificate.notifications.issued', ['serial' => $issue->serial_number]));
                $action->success();
                $livewire->js('window.open('.Js::from(route('certificates.download', $issue)).', "_blank")');
            });
    }
}
