@props(['compact' => false, 'light' => false])

<div {{ $attributes->class(['flex items-center gap-3']) }}>
    <svg class="shrink-0 {{ $compact ? 'size-12' : 'size-16 xl:size-[4.5rem]' }}" viewBox="0 0 72 78" fill="none" aria-hidden="true">
        <defs>
            <linearGradient id="logo-face-{{ $compact ? 'compact' : 'full' }}" x1="10" y1="7" x2="61" y2="72" gradientUnits="userSpaceOnUse">
                <stop stop-color="#31B7FF"/>
                <stop offset=".52" stop-color="#1769E8"/>
                <stop offset="1" stop-color="#623BDE"/>
            </linearGradient>
        </defs>
        <path d="M38 4C20.3 4 8 16.1 8 33.3c0 11.5 5.5 20 13.1 25.2V73l13.2-7.2c2.1.4 4 .6 5.8.6 14.6 0 25.9-12.2 25.9-29C66 18.8 54.9 4 38 4Z" fill="url(#logo-face-{{ $compact ? 'compact' : 'full' }})"/>
        <path d="m13 25 13-12 14 5 11-8M13 25l9 14 18-21m0 0 12 16-18 9-12-4m12 4 5 21m13-30 13 3M22 39l-1 19" stroke="#7DDCFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" opacity=".9"/>
        <g fill="#0FF0B8">
            <circle cx="13" cy="25" r="3"/><circle cx="26" cy="13" r="2.5"/><circle cx="40" cy="18" r="2.5"/>
        </g>
        <g fill="#2D7CFF">
            <circle cx="22" cy="39" r="3"/><circle cx="34" cy="43" r="2.5"/><circle cx="39" cy="64" r="2.5"/>
        </g>
        <circle cx="51" cy="10" r="3" fill="#FF9F1C"/>
        <circle cx="52" cy="34" r="2.5" fill="#9B5CFF"/>
        <path d="M41 66c9.4-1.1 17.2-8.7 20.7-18.7-7.7 3.2-16.8 5.2-26.8 5.7L41 66Z" fill="#102C7A" fill-opacity=".5"/>
    </svg>

    <div class="min-w-0">
        <div class="flex items-baseline font-extrabold tracking-[-0.055em] {{ $compact ? 'text-[1.65rem]' : 'text-3xl xl:text-[2.35rem]' }} {{ $light ? 'text-white' : 'text-[#07132f]' }}">
            <span>S<span class="relative inline-flex px-[.02em]">o<span class="absolute inset-0 flex items-center justify-center text-[.3em] font-black tracking-normal {{ $light ? 'text-[#06122f]' : 'text-white' }}">AI</span></span>ftSkills</span>
            <span class="ml-1 bg-gradient-to-r from-[#36adff] to-[#6755ff] bg-clip-text text-transparent">IA</span>
        </div>
        @unless ($compact)
            <p class="mt-0.5 text-[.61rem] font-semibold uppercase tracking-[.11em] {{ $light ? 'text-blue-100/80' : 'text-slate-500' }}">
                Evaluación de habilidades blandas impulsada por IA
            </p>
        @endunless
    </div>
</div>
