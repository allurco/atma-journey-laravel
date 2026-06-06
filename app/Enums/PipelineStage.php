<?php

declare(strict_types=1);

namespace App\Enums;

use App\Listeners\SyncPatientStatus;

/**
 * Stages of the patient retention funnel (the Kanban columns). Values match the
 * TS app. `Desistentes` is a special "drop" column, not part of the linear flow.
 */
enum PipelineStage: string
{
    case PrimeiroContato = 'primeiro_contato';
    case Avaliacao = 'avaliacao';
    case EmAnalise = 'em_analise';
    case OrcamentoEnviado = 'orcamento_enviado';
    case Negociando = 'negociando';
    case OrcamentoAceito = 'orcamento_aceito';
    case Agendado = 'agendado';
    case Retorno = 'retorno';
    case Concluido = 'concluido';
    case Desistentes = 'desistentes';

    public function label(): string
    {
        return match ($this) {
            self::PrimeiroContato => 'Primeiro Contato',
            self::Avaliacao => 'Avaliação',
            self::EmAnalise => 'Em Análise',
            self::OrcamentoEnviado => 'Orçamento Enviado',
            self::Negociando => 'Negociando',
            self::OrcamentoAceito => 'Orçamento Aceito',
            self::Agendado => 'Agendado',
            self::Retorno => 'Retorno',
            self::Concluido => 'Concluído',
            self::Desistentes => 'Desistentes',
        };
    }

    /**
     * The patient status this stage implies — the single source of the
     * stage→status map {@see SyncPatientStatus} applies.
     */
    public function patientStatus(): PatientStatus
    {
        return match ($this) {
            self::OrcamentoAceito, self::Agendado, self::Retorno, self::Concluido => PatientStatus::Ativo,
            self::Desistentes => PatientStatus::Inativo,
            default => PatientStatus::Lead,
        };
    }

    /**
     * The next stage in the linear funnel, or null at the end / off-flow.
     */
    public function next(): ?self
    {
        $flow = self::linearFlow();
        $index = array_search($this, $flow, true);

        return $index === false ? null : ($flow[$index + 1] ?? null);
    }

    /**
     * The previous stage in the linear funnel, or null at the start / off-flow.
     */
    public function previous(): ?self
    {
        $flow = self::linearFlow();
        $index = array_search($this, $flow, true);

        return $index === false || $index === 0 ? null : $flow[$index - 1];
    }

    /**
     * The linear funnel order — `Desistentes` excluded (it's a side column).
     *
     * @return list<self>
     */
    public static function linearFlow(): array
    {
        return [
            self::PrimeiroContato,
            self::Avaliacao,
            self::EmAnalise,
            self::OrcamentoEnviado,
            self::Negociando,
            self::OrcamentoAceito,
            self::Agendado,
            self::Retorno,
            self::Concluido,
        ];
    }

    /**
     * Tailwind classes for the column header band (bg + border), from Pipeline.tsx.
     */
    public function headerClasses(): string
    {
        return match ($this) {
            self::PrimeiroContato => 'bg-slate-50 border-slate-300',
            self::Avaliacao => 'bg-sky-50 border-sky-300',
            self::EmAnalise => 'bg-indigo-50 border-indigo-300',
            self::OrcamentoEnviado => 'bg-violet-50 border-violet-300',
            self::Negociando => 'bg-amber-50 border-amber-300',
            self::OrcamentoAceito => 'bg-lime-50 border-lime-300',
            self::Agendado => 'bg-teal-50 border-teal-300',
            self::Retorno => 'bg-cyan-50 border-cyan-300',
            self::Concluido => 'bg-emerald-50 border-emerald-300',
            self::Desistentes => 'bg-rose-50 border-rose-300',
        };
    }

    /**
     * Tailwind background for the small status dot in the column header.
     */
    public function dotClasses(): string
    {
        return match ($this) {
            self::PrimeiroContato => 'bg-slate-400',
            self::Avaliacao => 'bg-sky-400',
            self::EmAnalise => 'bg-indigo-400',
            self::OrcamentoEnviado => 'bg-violet-400',
            self::Negociando => 'bg-amber-500',
            self::OrcamentoAceito => 'bg-lime-500',
            self::Agendado => 'bg-teal-500',
            self::Retorno => 'bg-cyan-500',
            self::Concluido => 'bg-emerald-500',
            self::Desistentes => 'bg-rose-400',
        };
    }
}
