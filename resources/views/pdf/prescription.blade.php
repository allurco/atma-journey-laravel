<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: "DejaVu Sans", sans-serif; color: #1e293b; font-size: 12px; }
        .letterhead { border-bottom: 2px solid #0d9488; padding-bottom: 12px; margin-bottom: 24px; }
        .clinic-name { font-size: 18px; font-weight: bold; color: #0d9488; }
        .muted { color: #64748b; font-size: 11px; }
        .title { font-size: 14px; font-weight: bold; margin: 16px 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { text-align: left; padding: 6px 4px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
        th { color: #64748b; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; }
        .signature { margin-top: 72px; text-align: center; }
        .sig-line { border-top: 1px solid #1e293b; width: 260px; margin: 0 auto; padding-top: 6px; }
    </style>
</head>
<body>
    @unless ($clinic->uses_custom_prescription_paper)
        <div class="letterhead">
            <div class="clinic-name">{{ $clinic->name }}</div>
            @if ($clinic->address)<div class="muted">{{ $clinic->address }}</div>@endif
            @if ($clinic->phone)<div class="muted">{{ $clinic->phone }}</div>@endif
            @if ($clinic->cnpj)<div class="muted">CNPJ {{ $clinic->cnpj }}</div>@endif
        </div>
    @else
        {{-- Clinic prints onto its own pre-printed receituário — reserve top space. --}}
        <div style="height: {{ $clinic->prescription_header_margin_mm ?? 35 }}mm;"></div>
    @endunless

    <div class="title">Receita</div>
    <div class="muted">Paciente: {{ $prescription->patient->name }}</div>
    <div class="muted">Data: {{ $prescription->issued_at->format('d/m/Y') }}</div>

    <table>
        <thead>
            <tr><th>Medicamento</th><th>Dose</th><th>Frequência</th><th>Duração</th></tr>
        </thead>
        <tbody>
            @foreach ($prescription->items as $item)
                <tr>
                    <td>{{ $item['drug'] }}</td>
                    <td>{{ $item['dose'] ?? '' }}</td>
                    <td>{{ $item['frequency'] ?? '' }}</td>
                    <td>{{ $item['duration'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($prescription->notes)
        <p class="muted" style="margin-top: 16px;">{{ $prescription->notes }}</p>
    @endif

    <div class="signature">
        <div class="sig-line">
            {{ $prescription->doctor?->name }}<br>
            <span class="muted">CRM {{ $prescription->doctor?->crm }}</span>
        </div>
    </div>
</body>
</html>
