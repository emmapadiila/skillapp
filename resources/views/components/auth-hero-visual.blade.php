<div aria-hidden="true" {{ $attributes->class(['pointer-events-none absolute inset-0 overflow-hidden']) }}>
    <div class="auth-brain-glow absolute right-[-7%] top-[14%] h-[42%] w-[58%] opacity-90"></div>

    <svg class="absolute right-[-2%] top-[14%] h-[43%] w-[55%] text-blue-400/80" viewBox="0 0 420 360" fill="none">
        <defs>
            <filter id="brain-glow" x="-30%" y="-30%" width="160%" height="180%">
                <feGaussianBlur stdDeviation="3" result="blur"/>
                <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
            </filter>
            <linearGradient id="brain-stem" x1="210" y1="70" x2="210" y2="334" gradientUnits="userSpaceOnUse">
                <stop stop-color="#1EA7FF"/><stop offset="1" stop-color="#165DFF" stop-opacity="0"/>
            </linearGradient>
        </defs>
        <g stroke="currentColor" stroke-width="1" opacity=".7" filter="url(#brain-glow)">
            <path d="M82 143 113 92l59-31 70 7 59 34 32 54-7 58-38 37-56 15-57-7-55-25-31-46-7-45Z"/>
            <path d="m113 92 43 42 16-73m-16 73 73-28 13-38m-86 66-67 54m67-54 19 80m0 0 57-52-3-56m3 56 56 89m-56-89 94 52m-94-52 69-60m-126 112 57 52m-57-52-55 20m112 32 56-15m-56 15 10 91m46-106 38-37m-38 37-10-89m10 89 34 38M229 106l59 5 13-9m-72 4-57-45m57 45-54 108M113 92l-31 51m31-51 43 42m-67 54 86 26m-55 20 55-20m57-52 56-51"/>
        </g>
        <g fill="currentColor" filter="url(#brain-glow)">
            <circle cx="82" cy="143" r="3"/><circle cx="113" cy="92" r="3"/><circle cx="172" cy="61" r="3.5"/><circle cx="242" cy="68" r="3"/>
            <circle cx="301" cy="102" r="3.5"/><circle cx="333" cy="156" r="3"/><circle cx="326" cy="214" r="3"/><circle cx="288" cy="251" r="3.5"/>
            <circle cx="232" cy="266" r="3"/><circle cx="175" cy="259" r="3.5"/><circle cx="120" cy="234" r="3"/><circle cx="89" cy="188" r="3"/>
            <circle cx="156" cy="134" r="4"/><circle cx="229" cy="106" r="3.5"/><circle cx="288" cy="111" r="3"/><circle cx="232" cy="162" r="4"/>
            <circle cx="175" cy="214" r="3.5"/><circle cx="288" cy="201" r="4"/>
        </g>
        <path d="M232 266c-7 28-3 48 18 63M175 259c8 37 24 61 51 77M201 261v75m-43-82c-8 35-4 60 10 84m92-78c20 22 31 47 29 74" stroke="url(#brain-stem)" stroke-width="1.2"/>
    </svg>

    <div class="auth-orbit absolute right-[-14%] top-[39%] h-[38%] w-[67%] rounded-[50%] border border-blue-400/40"></div>
    <div class="auth-orbit auth-orbit--secondary absolute right-[-18%] top-[42%] h-[31%] w-[70%] rounded-[50%] border border-violet-400/25"></div>
    <div class="auth-wave absolute inset-x-0 bottom-0 h-[23%]"></div>
</div>
