<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\BudgetStatus;
use App\Enums\ContactType;
use App\Enums\PatientStatus;
use App\Enums\PipelineStage;
use App\Enums\TimelineEventType;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Doctor;
use App\Models\DoctorShift;
use App\Models\Patient;
use App\Models\PipelineCard;
use App\Models\Procedure;
use App\Models\Specialty;
use App\Models\TimelineEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Populates a tenant database with a coherent, presentation-ready dataset for a
 * live pitch demo: a full retention pipeline, a colourful week on the agenda,
 * budgets in various states, and patients overdue for recall so the dashboard
 * KPIs are non-zero.
 *
 * MUST run inside a tenant context (the `demo:seed` command guarantees this via
 * `$tenant->run(...)`). It never touches the central database.
 */
class DemoSeeder extends Seeder
{
    /**
     * When true, demo-owned tenant tables are wiped before seeding so a rehearsal
     * always starts from a clean slate. Set by the `demo:seed --fresh` command.
     */
    public bool $fresh = false;

    /** Shared demo password for the practitioner logins this seeder creates. */
    public const string DOCTOR_PASSWORD = 'demo1234';

    public function run(): void
    {
        if ($this->fresh) {
            $this->wipe();
        }

        $procedures = $this->seedCatalog();
        $doctors = $this->seedDoctors();
        $this->seedShifts($doctors);

        $patients = $this->seedPipeline($procedures);
        $this->seedAgenda($patients, $doctors, $procedures);

        $this->summarise();
    }

    /**
     * Specialties + procedures the clinic offers. Idempotent (firstOrCreate by
     * name), so the catalog is safe to re-run without `--fresh`.
     *
     * @return array<string, Procedure> keyed by procedure name
     */
    private function seedCatalog(): array
    {
        foreach (['Dermatologia', 'Estética', 'Nutrição'] as $name) {
            Specialty::query()->firstOrCreate(['name' => $name], ['active' => true]);
        }

        /** @var array<int, array{name: string, base_price: float, duration: int, category: string}> $catalog */
        $catalog = [
            ['name' => 'Avaliação Inicial', 'base_price' => 150.00, 'duration' => 30, 'category' => 'Avaliação'],
            ['name' => 'Limpeza de Pele', 'base_price' => 250.00, 'duration' => 60, 'category' => 'Facial'],
            ['name' => 'Microagulhamento', 'base_price' => 600.00, 'duration' => 60, 'category' => 'Facial'],
            ['name' => 'Peeling Químico', 'base_price' => 400.00, 'duration' => 45, 'category' => 'Facial'],
            ['name' => 'Preenchimento Labial', 'base_price' => 1200.00, 'duration' => 45, 'category' => 'Facial'],
            ['name' => 'Toxina Botulínica', 'base_price' => 1500.00, 'duration' => 30, 'category' => 'Facial'],
        ];

        $procedures = [];
        foreach ($catalog as $row) {
            $procedures[$row['name']] = Procedure::query()->firstOrCreate(
                ['name' => $row['name']],
                ['base_price' => $row['base_price'], 'duration' => $row['duration'], 'category' => $row['category'], 'active' => true],
            );
        }

        return $procedures;
    }

    /**
     * Three practitioners, each with a login (role `doctor`) so the "Meu dia"
     * worklist can be demoed.
     *
     * @return array<int, Doctor>
     */
    private function seedDoctors(): array
    {
        /** @var array<int, array{name: string, specialty: string, email: string}> $roster */
        $roster = [
            ['name' => 'Dra. Helena Martins', 'specialty' => 'Dermatologia', 'email' => 'helena@demo.atma'],
            ['name' => 'Dr. Rafael Souza', 'specialty' => 'Estética', 'email' => 'rafael@demo.atma'],
            ['name' => 'Dra. Camila Nunes', 'specialty' => 'Nutrição', 'email' => 'camila@demo.atma'],
        ];

        $doctors = [];
        foreach ($roster as $index => $row) {
            $doctor = Doctor::query()->firstOrCreate(
                ['email' => $row['email']],
                ['name' => $row['name'], 'crm' => 'CRM/SP '.(100200 + $index), 'phone' => '(11) 9'.(8000_0000 + $index), 'active' => true],
            );

            $specialty = Specialty::query()->where('name', $row['specialty'])->first();
            if ($specialty !== null) {
                $doctor->specialties()->syncWithoutDetaching([$specialty->id]);
            }

            User::query()->firstOrCreate(
                ['email' => $row['email']],
                ['name' => $row['name'], 'password' => self::DOCTOR_PASSWORD, 'role' => UserRole::Doctor, 'doctor_id' => $doctor->id, 'active' => true],
            );

            $doctors[] = $doctor;
        }

        return $doctors;
    }

    /**
     * Mon–Fri 08:00–18:00 availability for this week and next, so the calendar
     * shows open capacity and live bookings are accepted.
     *
     * @param  array<int, Doctor>  $doctors
     */
    private function seedShifts(array $doctors): void
    {
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);

        foreach ([0, 1] as $weekOffset) {
            for ($day = 0; $day < 5; $day++) {
                $date = $monday->copy()->addWeeks($weekOffset)->addDays($day)->format('Y-m-d');

                foreach ($doctors as $doctor) {
                    DoctorShift::query()->firstOrCreate(
                        ['doctor_id' => $doctor->id, 'date' => $date, 'start_time' => '08:00'],
                        ['end_time' => '18:00', 'unit' => null],
                    );
                }
            }
        }
    }

    /**
     * The heart of the demo: patients spread across every Kanban column, with
     * coherent status, budgets where the funnel implies one, timeline history,
     * and recall-overdue rollups so the dashboard KPIs light up.
     *
     * @param  array<string, Procedure>  $procedures
     * @return array<string, Patient> keyed by patient name (for the agenda pass)
     */
    private function seedPipeline(array $procedures): array
    {
        $specs = [
            // Topo do funil — leads frios, ainda sem orçamento.
            ['name' => 'Ana Beatriz Costa', 'stage' => PipelineStage::PrimeiroContato, 'treatment' => 'Avaliação facial', 'value' => 0, 'contactDays' => 0],
            ['name' => 'Bruno Carvalho', 'stage' => PipelineStage::PrimeiroContato, 'treatment' => 'Toxina botulínica', 'value' => 1500, 'contactDays' => 1],
            ['name' => 'Carla Mendes', 'stage' => PipelineStage::PrimeiroContato, 'treatment' => 'Preenchimento labial', 'value' => 1200, 'contactDays' => 2],
            ['name' => 'Diego Almeida', 'stage' => PipelineStage::Avaliacao, 'treatment' => 'Limpeza de pele', 'value' => 250, 'contactDays' => 1],
            ['name' => 'Eduarda Lima', 'stage' => PipelineStage::Avaliacao, 'treatment' => 'Microagulhamento', 'value' => 600, 'contactDays' => 3],
            ['name' => 'Felipe Rocha', 'stage' => PipelineStage::EmAnalise, 'treatment' => 'Peeling químico', 'value' => 400, 'contactDays' => 4],
            ['name' => 'Gabriela Pinto', 'stage' => PipelineStage::EmAnalise, 'treatment' => 'Protocolo facial completo', 'value' => 2300, 'contactDays' => 5],

            // Orçamento enviado — alvo do "aprovar ao vivo" (avança automaticamente).
            ['name' => 'Helena Barros', 'stage' => PipelineStage::OrcamentoEnviado, 'treatment' => 'Preenchimento labial', 'value' => 1200, 'contactDays' => 2, 'budget' => BudgetStatus::Sent],
            ['name' => 'Igor Fernandes', 'stage' => PipelineStage::OrcamentoEnviado, 'treatment' => 'Toxina botulínica', 'value' => 1500, 'contactDays' => 3, 'budget' => BudgetStatus::Sent],
            ['name' => 'Juliana Castro', 'stage' => PipelineStage::Negociando, 'treatment' => 'Microagulhamento (3 sessões)', 'value' => 1620, 'contactDays' => 1, 'budget' => BudgetStatus::Sent],
            ['name' => 'Lucas Moreira', 'stage' => PipelineStage::Negociando, 'treatment' => 'Protocolo corporal', 'value' => 3200, 'contactDays' => 2, 'budget' => BudgetStatus::Sent],

            // Fundo do funil — pacientes ativos, com histórico e LTV.
            ['name' => 'Mariana Dias', 'stage' => PipelineStage::OrcamentoAceito, 'treatment' => 'Preenchimento labial', 'value' => 1200, 'contactDays' => 1, 'budget' => BudgetStatus::Approved, 'ltv' => 1200, 'visits' => 1],
            ['name' => 'Natália Gomes', 'stage' => PipelineStage::OrcamentoAceito, 'treatment' => 'Toxina + peeling', 'value' => 1900, 'contactDays' => 2, 'budget' => BudgetStatus::Approved, 'ltv' => 1900, 'visits' => 2],
            ['name' => 'Otávio Ramos', 'stage' => PipelineStage::Agendado, 'treatment' => 'Microagulhamento', 'value' => 600, 'contactDays' => 0, 'ltv' => 600, 'visits' => 1],
            ['name' => 'Patrícia Nogueira', 'stage' => PipelineStage::Retorno, 'treatment' => 'Manutenção facial', 'value' => 400, 'contactDays' => 7, 'ltv' => 2400, 'visits' => 4, 'recallMonths' => 7],
            ['name' => 'Rodrigo Teixeira', 'stage' => PipelineStage::Concluido, 'treatment' => 'Protocolo facial concluído', 'value' => 3600, 'contactDays' => 20, 'budget' => BudgetStatus::Completed, 'ltv' => 3600, 'visits' => 6, 'recallMonths' => 8],
            ['name' => 'Sônia Albuquerque', 'stage' => PipelineStage::Concluido, 'treatment' => 'Clareamento concluído', 'value' => 1800, 'contactDays' => 35, 'budget' => BudgetStatus::Completed, 'ltv' => 1800, 'visits' => 3, 'recallMonths' => 9],

            // Desistentes — a coluna que o pitch quer "recuperar".
            ['name' => 'Thiago Barbosa', 'stage' => PipelineStage::Desistentes, 'treatment' => 'Preenchimento labial', 'value' => 1200, 'contactDays' => 45],
            ['name' => 'Vanessa Cardoso', 'stage' => PipelineStage::Desistentes, 'treatment' => 'Toxina botulínica', 'value' => 1500, 'contactDays' => 60],
        ];

        $procedureList = array_values($procedures);
        $patients = [];

        foreach ($specs as $index => $spec) {
            // Explicit, deterministic data — no Faker (a dev-only dependency that
            // is absent in production). firstOrCreate keyed on the demo e-mail keeps
            // re-runs idempotent without colliding on the unique CPF index.
            $patient = Patient::query()->firstOrCreate(
                ['email' => $this->demoEmail($spec['name'])],
                [
                    'name' => $spec['name'],
                    'phone' => sprintf('(11) 9%04d-%04d', 1100 + $index, 2200 + $index),
                    'cpf' => $this->demoCpf($index),
                    'birth_date' => Carbon::create(1980, 1, 1)->addDays($index * 53)->format('Y-m-d'),
                    'address' => 'Rua das Demonstrações, '.(100 + $index).' — São Paulo/SP',
                    'status' => $spec['stage']->patientStatus(),
                    'blood_type' => ['A+', 'O+', 'B+', 'AB+', 'O-'][$index % 5],
                    'allergies' => [],
                    'lead_source' => ['website', 'meta', 'google', 'referral', 'manual'][$index % 5],
                ],
            );

            // Denormalized rollups (not mass-assignable) — drive the dashboard KPIs.
            $visits = $spec['visits'] ?? 0;
            if ($visits > 0 || isset($spec['recallMonths'])) {
                $lastVisit = isset($spec['recallMonths'])
                    ? Carbon::now()->subMonths($spec['recallMonths'])
                    : Carbon::now()->subDays($spec['contactDays']);

                $patient->forceFill([
                    'ltv' => $spec['ltv'] ?? 0,
                    'total_appointments' => $visits,
                    'missed_appointments' => 0,
                    'first_visit_date' => Carbon::now()->subMonths(($spec['recallMonths'] ?? 1) + $visits),
                    'last_visit_date' => $lastVisit,
                ])->save();
            }

            $budget = isset($spec['budget'])
                ? ($patient->budgets()->first() ?? $this->makeBudget($patient, (float) $spec['value'], $spec['budget'], $procedureList[$index % count($procedureList)]))
                : null;

            PipelineCard::query()->updateOrCreate(
                ['patient_id' => $patient->id],
                [
                    'stage' => $spec['stage'],
                    'treatment' => $spec['treatment'],
                    'value' => $spec['value'],
                    'last_contact' => Carbon::now()->subDays($spec['contactDays']),
                    'contact_type' => ContactType::cases()[$index % count(ContactType::cases())],
                    'budget_id' => $budget?->id,
                ],
            );

            $this->makeTimeline($patient, $spec['stage']);

            $patients[$spec['name']] = $patient;
        }

        return $patients;
    }

    /**
     * A single-line budget whose total matches the card value, in the given state.
     */
    private function makeBudget(Patient $patient, float $total, BudgetStatus $status, Procedure $procedure): Budget
    {
        $budget = Budget::query()->create([
            'patient_id' => $patient->id,
            'total' => $total,
            'status' => $status,
            'notes' => null,
        ]);

        BudgetItem::query()->create([
            'budget_id' => $budget->id,
            'procedure_id' => $procedure->id,
            'name' => $procedure->name,
            'unit_price' => $total,
            'quantity' => 1,
            'discount' => 0,
        ]);

        return $budget;
    }

    /**
     * A short, believable activity log so the patient timeline isn't empty.
     */
    private function makeTimeline(Patient $patient, PipelineStage $stage): void
    {
        // Idempotent: a re-run (without --fresh) must not pile up duplicate events.
        if ($patient->timelineEvents()->exists()) {
            return;
        }

        TimelineEvent::query()->create([
            'patient_id' => $patient->id,
            'type' => TimelineEventType::Whatsapp,
            'title' => 'Primeiro contato via WhatsApp',
            'description' => 'Paciente demonstrou interesse no tratamento.',
            'occurred_at' => Carbon::now()->subDays(12),
        ]);

        if ($stage->patientStatus() === PatientStatus::Ativo) {
            TimelineEvent::query()->create([
                'patient_id' => $patient->id,
                'type' => TimelineEventType::Concluido,
                'title' => 'Orçamento aprovado',
                'description' => 'Tratamento iniciado.',
                'occurred_at' => Carbon::now()->subDays(6),
            ]);
        }

        if ($stage === PipelineStage::Desistentes) {
            TimelineEvent::query()->create([
                'patient_id' => $patient->id,
                'type' => TimelineEventType::LigacaoPerdida,
                'title' => 'Ligação não atendida',
                'description' => 'Tentativa de retomada sem retorno.',
                'occurred_at' => Carbon::now()->subDays(3),
            ]);
        }
    }

    /**
     * Appointments across the current week, colour-coded by service type and in
     * mixed states — including a scheduled one for "Otávio Ramos" (the live
     * no-show target) and completed visits earlier today (the "Meu dia" worklist).
     *
     * @param  array<string, Patient>  $patients
     * @param  array<int, Doctor>  $doctors
     * @param  array<string, Procedure>  $procedures
     */
    private function seedAgenda(array $patients, array $doctors, array $procedures): void
    {
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $helena = $doctors[0];

        /**
         * @var array<int, array{patient: string, day: int, start: string, service: string, status: AppointmentStatus, procedure: string, doctor?: int}> $slots
         */
        $slots = [
            // Hoje, já concluídos — povoam "Meu dia" da Dra. Helena.
            ['patient' => 'Mariana Dias', 'day' => $this->todayOffset($monday), 'start' => '09:00', 'service' => 'Consulta', 'status' => AppointmentStatus::Completed, 'procedure' => 'Preenchimento Labial', 'doctor' => 0],
            ['patient' => 'Natália Gomes', 'day' => $this->todayOffset($monday), 'start' => '10:30', 'service' => 'Consulta', 'status' => AppointmentStatus::CheckedIn, 'procedure' => 'Toxina Botulínica', 'doctor' => 0],
            // Alvo do no-show ao vivo: agendado, card ainda em "Agendado".
            ['patient' => 'Otávio Ramos', 'day' => $this->todayOffset($monday), 'start' => '14:00', 'service' => 'Consulta', 'status' => AppointmentStatus::Scheduled, 'procedure' => 'Microagulhamento', 'doctor' => 0],
            // Restante da semana — agenda colorida.
            ['patient' => 'Patrícia Nogueira', 'day' => 1, 'start' => '11:00', 'service' => 'Retorno', 'status' => AppointmentStatus::Scheduled, 'procedure' => 'Limpeza de Pele', 'doctor' => 1],
            ['patient' => 'Rodrigo Teixeira', 'day' => 2, 'start' => '15:00', 'service' => 'Retorno', 'status' => AppointmentStatus::Scheduled, 'procedure' => 'Avaliação Inicial', 'doctor' => 0],
            ['patient' => 'Diego Almeida', 'day' => 3, 'start' => '09:30', 'service' => 'Exame', 'status' => AppointmentStatus::Scheduled, 'procedure' => 'Avaliação Inicial', 'doctor' => 2],
            ['patient' => 'Eduarda Lima', 'day' => 4, 'start' => '16:00', 'service' => 'Consulta', 'status' => AppointmentStatus::Scheduled, 'procedure' => 'Microagulhamento', 'doctor' => 1],
        ];

        foreach ($slots as $slot) {
            $patient = $patients[$slot['patient']] ?? null;
            if ($patient === null) {
                continue;
            }

            $procedure = $procedures[$slot['procedure']] ?? null;
            $doctor = $doctors[$slot['doctor'] ?? 0] ?? $helena;
            [$hour, $minute] = explode(':', $slot['start']);
            $end = sprintf('%02d:%02d', (int) $hour + 1, (int) $minute);

            Appointment::query()->updateOrCreate(
                [
                    'patient_id' => $patient->id,
                    'date' => $monday->copy()->addDays($slot['day'])->format('Y-m-d'),
                    'start_time' => $slot['start'],
                ],
                [
                    'doctor_id' => $doctor->id,
                    'procedure_id' => $procedure?->id,
                    'service_type' => $slot['service'],
                    'end_time' => $end,
                    'status' => $slot['status'],
                ],
            );
        }
    }

    /**
     * Weekday index (0=Mon … 4=Fri) of today, clamped into the work week so the
     * "today" appointments always land on a visible column.
     */
    private function todayOffset(Carbon $monday): int
    {
        $offset = (int) $monday->diffInDays(Carbon::now()->startOfDay(), false);

        return max(0, min(4, $offset));
    }

    /**
     * Deterministic, namespaced e-mail used as the idempotency key for a demo
     * patient (so re-runs match the existing row instead of duplicating it).
     */
    private function demoEmail(string $name): string
    {
        return Str::slug($name).'@paciente.demo';
    }

    /**
     * A clearly-fake but unique (per index) formatted CPF — no Faker required.
     */
    private function demoCpf(int $index): string
    {
        return sprintf('%03d.%03d.%03d-%02d', 100 + $index, 200 + $index, 300 + $index, ($index % 89) + 10);
    }

    /**
     * Deletes the demo-owned tenant tables (never users-with-admin/staff, never
     * the clinic). Foreign-key checks are suspended so order doesn't matter.
     */
    private function wipe(): void
    {
        Schema::disableForeignKeyConstraints();

        TimelineEvent::query()->delete();
        BudgetItem::query()->delete();
        Budget::query()->delete();
        Appointment::query()->delete();
        DoctorShift::query()->delete();
        PipelineCard::query()->delete();
        DB::table('patient_phone_history')->delete();
        Patient::query()->delete();

        if (Schema::hasTable('doctor_specialty')) {
            DB::table('doctor_specialty')->delete();
        }
        Doctor::query()->delete();
        Procedure::query()->delete();
        Specialty::query()->delete();

        // Only the practitioner logins this seeder created — leave admins/staff.
        User::query()->where('role', UserRole::Doctor)->delete();

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Prints the cheat-sheet the presenter needs for the two "magic moment"
     * interactions and the practitioner login.
     */
    private function summarise(): void
    {
        $command = $this->command;

        $command->newLine();
        $command->info('  Dados de demonstração prontos.');
        $command->line('  • Pipeline cheio nas 10 colunas, agenda colorida na semana, KPIs do dashboard ativos.');
        $command->newLine();
        $command->line('  <fg=yellow>Momentos "mágicos" para a banca:</>');
        $command->line('  1. No-show ao vivo → use o agendamento de <fg=cyan>Otávio Ramos</> (hoje, 14:00).');
        $command->line('     Marque "Não compareceu" e o card dele cai sozinho em Desistentes.');
        $command->line('  2. Aprovar orçamento ao vivo → paciente <fg=cyan>Helena Barros</> (coluna Orçamento Enviado).');
        $command->line('     Aprove o orçamento e o card avança sozinho para Orçamento Aceito.');
        $command->newLine();
        $command->line('  Login de médico (tela "Meu dia"): <fg=cyan>helena@demo.atma</> / senha <fg=cyan>'.self::DOCTOR_PASSWORD.'</>');
        $command->newLine();
    }
}
