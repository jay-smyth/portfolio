{{-- Numbered chapter list for a guide, with section links under the current page. --}}
<ol class="space-y-1 text-sm">
    @foreach ([null, ...array_keys($guide->chapters)] as $index => $slug)
        @php $current = $slug === $chapter; @endphp
        <li>
            <a href="{{ $slug ? route('blog.chapter', [$guide->slug, $slug]) : route('blog.guide', $guide->slug) }}" wire:navigate
                @class([
                    'flex items-start gap-3 rounded-md px-2 py-1.5 transition',
                    'bg-zinc-100 font-medium text-zinc-900 dark:bg-zinc-800 dark:text-white' => $current,
                    'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800/50 dark:hover:text-white' => ! $current,
                ])
                @if ($current) aria-current="page" @endif>
                <span @class([
                    'mt-px flex size-5 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold tabular-nums',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $current,
                    'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300' => ! $current,
                ])>{{ $index }}</span>
                <span>{{ $slug ? $guide->chapters[$slug] : $guide->introduction }}</span>
            </a>

            @if ($current && count($page->headings) > 1)
                <ul class="mb-2 ml-[1.375rem] mt-1 space-y-1 border-l border-zinc-200 pl-4 dark:border-zinc-700" aria-label="On this page">
                    @foreach ($page->headings as $heading)
                        <li>
                            <a href="#{{ $heading['id'] }}" class="block py-0.5 text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                {{ $heading['text'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
</ol>
