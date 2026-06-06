<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle of a budget (orçamento). Values match the TS app. The flow is linear:
 * draft → sent → approved → completed.
 */
enum BudgetStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Approved = 'approved';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Sent => 'Enviado',
            self::Approved => 'Aprovado',
            self::Completed => 'Concluído',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-600',
            self::Sent => 'bg-blue-50 text-blue-700',
            self::Approved => 'bg-emerald-50 text-emerald-700',
            self::Completed => 'bg-teal-50 text-teal-700',
        };
    }

    /**
     * The next status in the linear flow, or null at the end.
     */
    public function next(): ?self
    {
        return match ($this) {
            self::Draft => self::Sent,
            self::Sent => self::Approved,
            self::Approved => self::Completed,
            self::Completed => null,
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return $this->next() === $status;
    }
}
