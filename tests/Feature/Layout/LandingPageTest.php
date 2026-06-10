<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

test('the landing page renders without the Atma Soma method name', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('jornada')           // the patient-journey framing remains
        ->assertDontSee('Atma Soma');    // the clinical method name is gone
});

/**
 * Static guard: the "Método Atma Soma" method name should not surface in any
 * Blade view. The product is "ATMA Journey" — the underlying method is no longer
 * named on any screen.
 */
test('no Blade view names the Atma Soma method', function () {
    $offenders = [];

    foreach (File::allFiles(resource_path('views')) as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        if (str_contains($file->getContents(), 'Atma Soma')) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});
