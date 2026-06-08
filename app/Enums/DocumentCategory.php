<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Categories for a patient document in the prontuário. `Exam` is the kind the
 * AI features (PRD-10 parsing, PRD-16 clinical signals) later consume.
 */
enum DocumentCategory: string
{
    case Exam = 'exam';
    case Report = 'report';
    case Consent = 'consent';
    case Contract = 'contract';
    case Questionnaire = 'questionnaire';
    case Image = 'image';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Exam => 'Exame',
            self::Report => 'Laudo',
            self::Consent => 'Termo',
            self::Contract => 'Contrato',
            self::Questionnaire => 'Questionário',
            self::Image => 'Imagem',
            self::Other => 'Outro',
        };
    }
}
