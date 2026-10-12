<x-layouts.public title="Blog" description="Guides and write-ups on building and deploying web applications.">
    <section class="mx-auto max-w-4xl px-4 pb-8 pt-16 sm:px-6 sm:pt-24">
        <h1 class="text-4xl font-semibold tracking-tight text-zinc-900 dark:text-white">Blog</h1>
        <p class="mt-2 text-lg text-zinc-500 dark:text-zinc-400">
            Step-by-step guides and write-ups on building and deploying web applications.
        </p>
    </section>

    <section class="mx-auto max-w-4xl px-4 pb-20 sm:px-6">
        @forelse ($guides as $guide)
            <article class="group relative rounded-xl border border-zinc-200 p-6 transition hover:-translate-y-0.5 hover:shadow-lg sm:p-8 dark:border-zinc-800 dark:hover:shadow-black/40">
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                    <span class="font-medium text-zinc-900 dark:text-white">Guide</span>
                    <span aria-hidden="true">&middot;</span>
                    <time datetime="{{ $guide->published->toDateString() }}">{{ $guide->published->format('j F Y') }}</time>
                    <span aria-hidden="true">&middot;</span>
                    <span>{{ count($guide->chapters) }} steps</span>
                    <span aria-hidden="true">&middot;</span>
                    <span>{{ $guide->readingMinutes() }} min read</span>
                </div>

                <h2 class="mt-3 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                    <a href="{{ route('blog.guide', $guide->slug) }}" wire:navigate class="after:absolute after:inset-0">
                        {{ $guide->title }}
                    </a>
                </h2>
                <p class="mt-3 text-zinc-600 dark:text-zinc-400">{{ $guide->summary }}</p>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($guide->tags as $tag)
                            <flux:badge size="sm">{{ $tag }}</flux:badge>
                        @endforeach
                    </div>
                    <span class="text-sm font-medium text-zinc-900 dark:text-white">Start reading &rarr;</span>
                </div>
            </article>
        @empty
            <p class="rounded-xl border border-dashed border-zinc-300 p-10 text-center text-zinc-500 dark:border-zinc-700">
                Posts are on their way — check back soon.
            </p>
        @endforelse
    </section>
</x-layouts.public>
