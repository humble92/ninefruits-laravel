<?php

namespace App\Filament\Admin\Resources\Evaluation\Forms\RelationManagers;

use App\Domain\Evaluation\Enums\QuestionType;
use App\Filament\Admin\Support\TranslatableFields;
use App\Models\Evaluation\EvaluationForm;
use App\Models\Evaluation\EvaluationQuestion;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * The questions of a form. Locked once the form has responses (results must keep meaning
 * what was asked); copy the form to change them.
 */
class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('evaluation.fields.questions');
    }

    public function isReadOnly(): bool
    {
        /** @var EvaluationForm $form */
        $form = $this->getOwnerRecord();

        return $form->isLocked() || ! Gate::allows('update', $form);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TranslatableFields::make('category', __('evaluation.fields.category'), required: false)->columnSpanFull(),
            TranslatableFields::make('text', __('evaluation.fields.question'))->columnSpanFull(),
            Select::make('question_type')->label(__('evaluation.fields.question_type'))->required()->default(QuestionType::Scale->value)
                ->options(collect(QuestionType::cases())->mapWithKeys(fn (QuestionType $type) => [$type->value => $type->label()])),
            TextInput::make('sort_order')->label(__('reference.fields.sort_order'))->integer()->default(fn () => (EvaluationQuestion::query()->where('evaluation_form_id', $this->getOwnerRecord()->getKey())->max('sort_order') ?? 0) + 10),
            Toggle::make('is_required')->label(__('evaluation.fields.is_required'))->default(true),
            Toggle::make('is_reversed')->label(__('evaluation.fields.is_reversed'))->helperText(__('evaluation.help.is_reversed')),
        ]);
    }

    public function table(Table $table): Table
    {
        $translations = fn (array $data, EvaluationQuestion $record) => [...$data, 'category' => $record->getTranslations('category'), 'text' => $record->getTranslations('text')];

        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#'),
                TextColumn::make('category')->label(__('evaluation.fields.category'))->placeholder('-'),
                TextColumn::make('text')->label(__('evaluation.fields.question'))->wrap(),
                TextColumn::make('question_type')->label(__('evaluation.fields.question_type'))->formatStateUsing(fn (QuestionType $state) => $state->label()),
                IconColumn::make('is_required')->label(__('evaluation.fields.is_required'))->boolean(),
                IconColumn::make('is_reversed')->label(__('evaluation.fields.is_reversed'))->boolean(),
            ])
            ->defaultSort('sort_order')
            ->paginated(false)
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()->mutateRecordDataUsing($translations), DeleteAction::make()]);
    }
}
