@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#FAFAF8] text-slate-800 antialiased">
        {{ $slot }}

        @fluxScripts
    </body>
</html>
