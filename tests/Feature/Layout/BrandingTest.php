<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\File;

test('the authenticated shell shows ATMA branding and no Laravel mention', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('ATMA Journey')
        ->assertDontSee('Laravel');
});

test('the login screen shows ATMA branding and no Laravel mention', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('Laravel');
});

/**
 * Static guard over every Blade view: the Livewire Starter Kit shipped Laravel
 * marketing — the "Laravel Starter Kit" brand name, the Laravel hexagon logo
 * (x-app-logo-icon), and footer links to laravel.com / github.com/laravel. None
 * of it should survive in any screen, routed or leftover, so a re-scaffold can't
 * silently reintroduce it.
 */
test('no Blade view carries Laravel starter-kit branding', function () {
    $forbidden = [
        'Laravel Starter Kit',
        'github.com/laravel',
        'laravel.com/docs',
        'x-app-logo-icon', // the Laravel hexagon logo component
    ];

    $offenders = [];

    foreach (File::allFiles(resource_path('views')) as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = $file->getContents();

        foreach ($forbidden as $needle) {
            if (str_contains($contents, $needle)) {
                $offenders[] = $file->getRelativePathname().' → '.$needle;
            }
        }
    }

    expect($offenders)->toBe([]);
});
