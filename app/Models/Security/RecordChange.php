<?php

namespace App\Models\Security;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Row written by the audit.record_change() trigger. Read only.
 *
 * @property int $id
 * @property CarbonImmutable $occurred_at
 * @property int $transaction_id
 * @property string $table_schema
 * @property string $table_name
 * @property string $operation
 * @property string|null $record_id
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property list<string>|null $changed_columns
 * @property int|null $user_id
 * @property string|null $actor
 * @property string|null $request_id
 * @property string $database_user
 */
class RecordChange extends Model
{
    protected $table = 'audit.record_changes';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'occurred_at' => 'immutable_datetime',
            'old_values' => 'array',
            'new_values' => 'array',
            'changed_columns' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
