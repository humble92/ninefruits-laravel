<?php

namespace App\Providers;

use App\Domain\Person\Personas\AdvisorPersonaDetector;
use App\Domain\Person\Personas\AlumniPersonaDetector;
use App\Domain\Person\Personas\InstructorPersonaDetector;
use App\Domain\Person\Personas\StudentPersonaDetector;
use App\Domain\Security\Contracts\PersonaDetector;
use App\Domain\Security\Enums\Permission;
use App\Domain\Security\Listeners\RecordAuthenticationEvents;
use App\Domain\Security\Services\AuthorizationService;
use App\Domain\Security\Services\PersonaResolver;
use App\Http\Middleware\EnforceMfaPolicy;
use App\Http\Middleware\SetDatabaseSessionContext;
use App\Http\Middleware\SetLocale;
use App\Models\Curriculum\CourseCategory;
use App\Models\Facility\Building;
use App\Models\Facility\Room;
use App\Models\Grading\GradeScale;
use App\Models\Grading\GradeScaleItem;
use App\Models\Institution\Country;
use App\Models\Institution\Language;
use App\Models\Institution\LookupType;
use App\Models\Institution\LookupValue;
use App\Models\Organization\OrgUnit;
use App\Models\Organization\OrgUnitSuccession;
use App\Models\Organization\OrgUnitType;
use App\Models\Organization\Program;
use App\Models\Security\Activity;
use App\Models\Security\RecordChange;
use App\Models\Security\Role;
use App\Models\Security\RoleAssignment;
use App\Models\Security\SecurityEvent;
use App\Models\Term\AcademicYear;
use App\Models\Term\Term;
use App\Models\Term\TermType;
use App\Models\Term\TermWindow;
use App\Models\Term\TimeSlot;
use App\Models\User;
use App\Policies\Reference\CourseCategoryPolicy;
use App\Policies\Reference\FacilityPolicy;
use App\Policies\Reference\GradeScalePolicy;
use App\Policies\Reference\LookupDataPolicy;
use App\Policies\Reference\OrgUnitPolicy;
use App\Policies\Reference\ProgramPolicy;
use App\Policies\Reference\TermPolicy;
use App\Policies\Reference\TermWindowPolicy;
use App\Policies\Security\AuditRecordPolicy;
use App\Policies\Security\RoleAssignmentPolicy;
use App\Policies\Security\RolePolicy;
use App\Policies\Security\SecurityEventPolicy;
use App\Support\Pdf\GotenbergPdfRenderer;
use App\Support\Pdf\PdfRenderer;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Spatie\Activitylog\Actions\LogActivityAction;
use Spatie\Activitylog\Contracts\Activity as ActivityContract;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Persona detectors registered by domain contexts (tag: security.persona_detectors).
     *
     * @var list<class-string<PersonaDetector>>
     */
    private const array PERSONA_DETECTORS = [
        StudentPersonaDetector::class,
        AlumniPersonaDetector::class,
        InstructorPersonaDetector::class,
        AdvisorPersonaDetector::class,
    ];

    /**
     * @var array<class-string, class-string>
     */
    private const array REFERENCE_POLICIES = [
        Country::class => LookupDataPolicy::class,
        Language::class => LookupDataPolicy::class,
        LookupType::class => LookupDataPolicy::class,
        LookupValue::class => LookupDataPolicy::class,
        OrgUnitType::class => OrgUnitPolicy::class,
        OrgUnit::class => OrgUnitPolicy::class,
        OrgUnitSuccession::class => OrgUnitPolicy::class,
        Program::class => ProgramPolicy::class,
        Building::class => FacilityPolicy::class,
        Room::class => FacilityPolicy::class,
        TermType::class => TermPolicy::class,
        AcademicYear::class => TermPolicy::class,
        Term::class => TermPolicy::class,
        TimeSlot::class => TermPolicy::class,
        TermWindow::class => TermWindowPolicy::class,
        CourseCategory::class => CourseCategoryPolicy::class,
        GradeScale::class => GradeScalePolicy::class,
        GradeScaleItem::class => GradeScalePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->tag(self::PERSONA_DETECTORS, 'security.persona_detectors');

        $this->app->scoped(PersonaResolver::class, fn ($app) => new PersonaResolver($app->tagged('security.persona_detectors')));
        $this->app->scoped(AuthorizationService::class);

        $this->app->bind(PdfRenderer::class, GotenbergPdfRenderer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureAuthorization();
        $this->configureAudit();

        Event::subscribe(RecordAuthenticationEvents::class);

        Translatable::fallback(fallbackLocale: config('app.fallback_locale'), fallbackAny: true);

        Password::defaults(fn () => Password::min(12)->uncompromised(app()->isProduction() ? 0 : PHP_INT_MAX));

        Livewire::addPersistentMiddleware([
            SetDatabaseSessionContext::class,
            SetLocale::class,
            EnforceMfaPolicy::class,
        ]);
    }

    private function configureAuthorization(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(RoleAssignment::class, RoleAssignmentPolicy::class);
        Gate::policy(Activity::class, AuditRecordPolicy::class);
        Gate::policy(RecordChange::class, AuditRecordPolicy::class);
        Gate::policy(SecurityEvent::class, SecurityEventPolicy::class);

        foreach (self::REFERENCE_POLICIES as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Abilities named after catalog permissions ("users.view") are answered from role
        // assignments and personas. Other abilities fall through to policies.
        Gate::before(function (User $user, string $ability): ?bool {
            if (Permission::tryFrom($ability) === null) {
                return null;
            }

            return app(AuthorizationService::class)->hasPermission($user, $ability) ? true : null;
        });
    }

    private function configureAudit(): void
    {
        LogActivityAction::beforeLogging(function (ActivityContract $activity): void {
            if (! $activity instanceof Activity) {
                return;
            }

            $activity->request_id ??= Context::get('request_id');
            $activity->ip_address ??= Context::get('ip_address');
            $activity->user_agent ??= Context::getHidden('user_agent');
        });
    }
}
