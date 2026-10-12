@php
    $position = $guide->position($chapter);
    $title = $chapter ? $guide->chapters[$chapter] : $guide->title;
    $metaTitle = $chapter ? "{$title} · {$guide->title}" : $title;
    $previous = $position > 1 ? route('blog.chapter', [$guide->slug, $guide->previous($chapter)]) : ($position === 1 ? route('blog.guide', $guide->slug) : null);
    $previousTitle = $position > 1 ? $guide->chapters[$guide->previous($chapter)] : $guide->introduction;
    $next = $guide->next($chapter);
@endphp

<x-layouts.public :title="$metaTitle" :description="$guide->summary">
    <div class="mx-auto max-w-6xl px-4 pt-10 sm:px-6 lg:grid lg:grid-cols-[16rem_minmax(0,1fr)] lg:gap-12 lg:pt-16">
        {{-- Guide contents: sticky sidebar on large screens --}}
        <aside class="hidden lg:block">
            <nav class="sticky top-24 max-h-[calc(100vh-7rem)] overflow-y-auto pb-10" aria-label="Guide contents">
                <a href="{{ route('blog.index') }}" wire:navigate class="text-xs font-semibold uppercase tracking-wide text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                    &larr; Blog
                </a>
                <p class="mb-3 mt-4 font-semibold text-zinc-900 dark:text-white">{{ $guide->title }}</p>
                @include('public.blog.partials.contents')
            </nav>
        </aside>

        <article class="min-w-0 pb-16">
            {{-- Guide contents: collapsible on small screens --}}
            <details class="mb-8 rounded-xl border border-zinc-200 lg:hidden dark:border-zinc-800">
                <summary class="cursor-pointer select-none px-4 py-3 text-sm font-medium text-zinc-900 dark:text-white">
                    Guide contents
                    <span class="font-normal text-zinc-500 dark:text-zinc-400">&middot; {{ $chapter ? "Step {$position} of ".count($guide->chapters) : 'Introduction' }}</span>
                </summary>
                <nav class="border-t border-zinc-200 p-2 dark:border-zinc-800" aria-label="Guide contents">
                    @include('public.blog.partials.contents')
                </nav>
            </details>

            <header class="max-w-3xl">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                    @if ($chapter)
                        Step {{ $position }} of {{ count($guide->chapters) }} &middot;
                        <a href="{{ route('blog.guide', $guide->slug) }}" wire:navigate class="hover:text-zinc-900 dark:hover:text-white">{{ $guide->title }}</a>
                    @else
                        Guide &middot; {{ count($guide->chapters) }} steps
                    @endif
                </p>

                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-zinc-900 sm:text-5xl dark:text-white">{{ $title }}</h1>

                @unless ($chapter)
                    <p class="mt-4 text-lg text-zinc-600 dark:text-zinc-400">{{ $guide->summary }}</p>
                @endunless

                <div class="mt-6 flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-zinc-200 pb-6 text-sm text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <time datetime="{{ $guide->published->toDateString() }}">{{ $guide->published->format('j F Y') }}</time>
                    <span aria-hidden="true">&middot;</span>
                    <span>{{ $page->minutes }} min read</span>
                    @foreach ($guide->tags as $tag)
                        <flux:badge size="sm">{{ $tag }}</flux:badge>
                    @endforeach
                </div>
            </header>

            <div class="markdown markdown-guide mt-8 max-w-3xl">
                {!! $page->html !!}
            </div>

            @unless ($chapter)
                <section class="mt-12 max-w-3xl rounded-xl border border-zinc-200 p-6 dark:border-zinc-800" aria-labelledby="guide-steps">
                    <h2 id="guide-steps" class="text-xl font-semibold text-zinc-900 dark:text-white">What you'll do in this guide</h2>
                    <ol class="mt-4 space-y-3">
                        @foreach ($guide->chapters as $slug => $chapterTitle)
                            <li class="flex gap-3">
                                <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold tabular-nums text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $loop->iteration }}</span>
                                <a href="{{ route('blog.chapter', [$guide->slug, $slug]) }}" wire:navigate class="font-medium text-zinc-900 underline-offset-4 hover:underline dark:text-white">{{ $chapterTitle }}</a>
                            </li>
                        @endforeach
                    </ol>
                    @if ($next)
                        <flux:button variant="primary" icon-trailing="arrow-right" class="mt-6" :href="route('blog.chapter', [$guide->slug, $next])" wire:navigate>
                            Start the guide
                        </flux:button>
                    @endif
                </section>
            @endunless

            @if ($previous || $next)
                <nav class="mt-12 grid max-w-3xl gap-4 border-t border-zinc-200 pt-8 sm:grid-cols-2 dark:border-zinc-800" aria-label="Guide navigation">
                    <div>
                        @if ($previous)
                            <a href="{{ $previous }}" wire:navigate class="block rounded-xl border border-zinc-200 p-4 transition hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600">
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">&larr; Previous</span>
                                <span class="mt-1 block font-medium text-zinc-900 dark:text-white">{{ $previousTitle }}</span>
                            </a>
                        @endif
                    </div>
                    <div>
                        @if ($next)
                            <a href="{{ route('blog.chapter', [$guide->slug, $next]) }}" wire:navigate class="block rounded-xl border border-zinc-200 p-4 text-right transition hover:border-zinc-400 dark:border-zinc-800 dark:hover:border-zinc-600">
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">Next &rarr;</span>
                                <span class="mt-1 block font-medium text-zinc-900 dark:text-white">{{ $guide->chapters[$next] }}</span>
                            </a>
                        @endif
                    </div>
                </nav>
            @endif
        </article>
    </div>
</x-layouts.public>
