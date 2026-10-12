<x-layouts.app title="Dashboard">
    <div class="flex w-full flex-col gap-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">Dashboard</flux:heading>
                <flux:subheading>An overview of your portfolio.</flux:subheading>
            </div>
            <div class="flex gap-2">
                <flux:button icon="arrow-top-right-on-square" :href="route('home')" target="_blank">View site</flux:button>
                <flux:button variant="primary" icon="plus" :href="route('admin.projects.create')" wire:navigate>New project</flux:button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as $label => $value)
                <flux:card>
                    <flux:subheading>{{ $label }}</flux:subheading>
                    <flux:heading size="xl" class="mt-2 tabular-nums">{{ $value }}</flux:heading>
                </flux:card>
            @endforeach
        </div>

        <div>
            <div class="mb-3 flex items-center justify-between">
                <flux:heading size="lg">Recently updated</flux:heading>
                <flux:link :href="route('admin.projects.index')" wire:navigate class="text-sm">All projects &rarr;</flux:link>
            </div>

            <flux:table>
                <flux:table.rows>
                    @forelse ($recent as $project)
                        <flux:table.row :key="$project->id">
                            <flux:table.cell>
                                <a href="{{ route('admin.projects.edit', $project) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-white">{{ $project->title }}</a>
                            </flux:table.cell>
                            <flux:table.cell>
                                @if ($project->is_published)
                                    <flux:badge size="sm" color="green">Published</flux:badge>
                                @else
                                    <flux:badge size="sm">Draft</flux:badge>
                                @endif
                                @if ($project->is_featured)
                                    <flux:badge size="sm" color="amber">Featured</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell align="end" class="text-zinc-500">{{ $project->updated_at->diffForHumans() }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell class="py-8 text-center text-zinc-500">No projects yet.</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>
</x-layouts.app>
