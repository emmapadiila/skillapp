@props(['title', 'activeNav' => 'dashboard', 'breadcrumb' => null])

<!DOCTYPE html>
<html lang="es" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @if (session('supabase.access_token'))<meta name="api-access-token" content="{{ session('supabase.access_token') }}">@endif
        <meta name="color-scheme" content="light">
        <meta name="theme-color" content="#0c1729">
        <title>{{ $title }} | SkillDiagnóstico</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-full bg-[#f4f7fb] font-sans text-[#17243a] antialiased">
        <div class="min-h-dvh">
            <x-admin.sidebar :active="$activeNav" />
            <div id="sidebar-backdrop" class="fixed inset-0 z-30 hidden bg-slate-950/45 lg:hidden" aria-hidden="true"></div>

            <main class="min-h-dvh lg:pl-64">
                <x-admin.topbar :title="$title" :breadcrumb="$breadcrumb" />
                <div id="workspace-content">{{ $slot }}</div>
                <x-admin.skills />
            </main>
        </div>

        <div id="interface-toast" class="pointer-events-none fixed bottom-5 left-1/2 z-[70] -translate-x-1/2 translate-y-3 rounded-lg bg-[#10213d] px-4 py-3 text-sm font-medium text-white opacity-0 shadow-xl transition duration-200" role="status" aria-live="polite"></div>
    </body>
</html>