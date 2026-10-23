<?php

namespace App\Domain\Evaluation\Enums;

enum QuestionType: string
{
    case Scale = 'scale';
    case Text = 'text';

    public function label(): string
    {
        return __('evaluation.question_types.'.$this->value);
    }
}
