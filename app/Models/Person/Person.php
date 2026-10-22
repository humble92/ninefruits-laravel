<?php

namespace App\Models\Person;

use App\Domain\Person\Enums\Gender;
use App\Domain\Security\Enums\Permission;
use App\Models\Concerns\HasEncryptedPii;
use App\Models\Contracts\EncryptsPersonalData;
use App\Models\Institution\Country;
use App\Models\Institution\LookupValue;
use App\Models\User;
use App\Support\Pii\PiiHasher;
use App\Support\Pii\PiiType;
use Carbon\CarbonImmutable;
use Database\Factories\Person\PersonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * A person, one row for life (docs/porting-plan.md section 10.1). Student records,
 * instructor records, guardians and the login account all point here.
 *
 * The legal name is romanized (family_name, given_name); name_variants holds the name as
 * written in other languages or scripts, e.g. {"ko": "김민수", "zh_CN": "金敏秀"}.
 *
 * @property int $id
 * @property string $family_name
 * @property string|null $given_name
 * @property array<string, string>|null $name_variants
 * @property CarbonImmutable|null $birth_date
 * @property Gender|null $gender
 * @property int|null $nationality_country_id
 * @property int|null $ethnicity_id
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $postal_code
 * @property string|null $national_id_encrypted
 * @property string|null $passport_number_encrypted
 * @property int|null $merged_into_person_id
 * @property int|null $created_by_id
 * @property-read User|null $user
 */
class Person extends Model implements EncryptsPersonalData, HasMedia
{
    /** @use HasFactory<PersonFactory> */
    use HasEncryptedPii, HasFactory, InteractsWithMedia;

    public const string PHOTO_COLLECTION = 'photo';

    /** Fields a person may change about themselves in the portal. */
    public const array SELF_EDITABLE = ['email', 'phone', 'address', 'postal_code'];

    protected $table = 'people';

    protected $fillable = [
        'family_name', 'given_name', 'name_variants', 'birth_date', 'gender', 'nationality_country_id', 'ethnicity_id',
        'email', 'phone', 'address', 'postal_code', 'national_id_encrypted', 'passport_number_encrypted',
    ];

    protected $attributes = [
        'given_name' => null, 'name_variants' => null, 'birth_date' => null, 'gender' => null, 'nationality_country_id' => null,
        'ethnicity_id' => null, 'email' => null, 'phone' => null, 'address' => null, 'postal_code' => null,
        'national_id_encrypted' => null, 'national_id_hash' => null, 'passport_number_encrypted' => null, 'passport_number_hash' => null,
        'merged_into_person_id' => null,
    ];

    protected function casts(): array
    {
        return [
            'name_variants' => 'array',
            'birth_date' => 'immutable_date',
            'gender' => Gender::class,
        ];
    }

    public function encryptedPiiAttributes(): array
    {
        return ['national_id' => PiiType::NationalId, 'passport_number' => PiiType::PassportNumber];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::PHOTO_COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk((string) config('media-library.disk_name'));
    }

    /**
     * Name for display: the variant for the locale (or its base language), else the
     * romanized name.
     */
    public function displayName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $variants = $this->name_variants ?? [];

        return $variants[$locale]
            ?? $variants[strtok($locale, '_')]
            ?? trim($this->given_name.' '.$this->family_name);
    }

    public function romanizedName(): string
    {
        return trim($this->given_name.' '.$this->family_name);
    }

    /**
     * @return HasOne<User, $this>
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /**
     * @return HasMany<StudentRecord, $this>
     */
    public function studentRecords(): HasMany
    {
        return $this->hasMany(StudentRecord::class)->orderByDesc('admission_date');
    }

    /**
     * @return HasOne<Instructor, $this>
     */
    public function instructor(): HasOne
    {
        return $this->hasOne(Instructor::class);
    }

    /**
     * @return HasMany<Guardian, $this>
     */
    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class)->orderByDesc('is_primary_contact')->orderBy('id');
    }

    /**
     * @return HasMany<AccountInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(AccountInvitation::class);
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'nationality_country_id');
    }

    /**
     * @return BelongsTo<LookupValue, $this>
     */
    public function ethnicity(): BelongsTo
    {
        return $this->belongsTo(LookupValue::class, 'ethnicity_id');
    }

    /**
     * @return BelongsTo<Person, $this>
     */
    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_person_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        self::constrainVisibleTo($query, $user);
    }

    /**
     * People the user may see: everyone with people.view, otherwise themselves, people they
     * created, people whose student records they can see, and instructors. Mirrors the row
     * level security policies on people.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function constrainVisibleTo(Builder $query, User $user): void
    {
        if ($user->can(Permission::PeopleView->value)) {
            return;
        }

        $query->where(fn (Builder $query) => $query
            ->when($user->person_id !== null, fn (Builder $query) => $query->orWhere('people.id', $user->person_id))
            ->orWhere('people.created_by_id', $user->id)
            ->orWhereHas('studentRecords', fn (Builder $records) => StudentRecord::constrainVisibleTo($records, $user))
            ->orWhereHas('instructor'));
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeNotMerged(Builder $query): void
    {
        $query->whereNull('merged_into_person_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        self::constrainToSearch($query, $term);
    }

    /**
     * Search by romanized name, any name variant, or exact national id / passport number.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public static function constrainToSearch(Builder $query, string $term): void
    {
        $term = trim($term);
        $hasher = app(PiiHasher::class);

        $query->where(function (Builder $query) use ($term, $hasher) {
            $query->whereRaw("(family_name || ' ' || coalesce(given_name, '') || ' ' || util.i18n_search_text(name_variants)) ILIKE ?", ['%'.$term.'%']);

            foreach ((new self)->encryptedPiiAttributes() as $name => $type) {
                $query->orWhere("{$name}_hash", $hasher->hash($term, $type));
            }
        });
    }

    public function isVisibleTo(User $user): bool
    {
        return self::query()->visibleTo($user)->whereKey($this->id)->exists();
    }
}
