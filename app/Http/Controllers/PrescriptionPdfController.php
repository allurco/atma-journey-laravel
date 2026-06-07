<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Prescription;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streams a prescription as a generated PDF. The layout honours the clinic's
 * `uses_custom_prescription_paper` setting — generated letterhead vs blank-top
 * for clinics that print onto their own pre-printed receituário.
 */
class PrescriptionPdfController
{
    public function __invoke(Prescription $prescription): Response
    {
        $prescription->load('doctor', 'patient');

        $pdf = Pdf::loadView('pdf.prescription', [
            'prescription' => $prescription,
            'clinic' => Clinic::current(),
        ]);

        return $pdf->stream("receita-{$prescription->id}.pdf");
    }
}
