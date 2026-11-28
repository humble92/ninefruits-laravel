<x-filament-panels::page>
    @if ($errors->has('year_level'))
        <p role="alert" class="text-danger-600">{{ $errors->first('year_level') }}</p>
    @endif
    @if ($run = $this->selectedRun())
        <x-filament::link :href="$this->getUrl()">{{ __('progression.back') }}</x-filament::link>
        <p>{{ $run->term->code }} · {{ $run->program?->code ?? __('progression.all_programs') }} · {{ __('progression.cutoff') }} {{ $run->cutoff_date->toDateString() }} · {{ __('progression.statuses.' . $run->status) }}
            @if ($run->scheduled_at)
                · {{ __('progression.scheduled_at') }} {{ $run->scheduled_at->setTimezone(app(\App\Domain\Institution\Settings\InstitutionSettings::class)->timezone)->format('Y-m-d H:i T') }}
            @endif
        </p>
        @if ($run->program_id === null)
            <div>
                <label for="review-program">{{ __('progression.review_program') }}</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select id="review-program" wire:model.live="reviewProgram">
                        <option value="">{{ __('progression.all_programs') }}</option>
                        @foreach ($this->reviewPrograms($run) as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        @endif
        @php($items = $this->changes($run))
        <div class="overflow-x-auto">
            <table class="w-full text-start text-sm">
                <thead><tr>
                    @foreach (['student', 'program', 'rule', 'measured', 'change', 'outcome'] as $heading)
                        <th scope="col" class="p-2 text-start">{{ __('progression.' . $heading) }}</th>
                    @endforeach
                    @if ($run->status === 'draft')<th></th>@endif
                </tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr class="border-t border-gray-200 dark:border-white/10" wire:key="change-{{ $item->id }}">
                            <td class="p-2">{{ $item->studentRecord->student_number }}</td>
                            <td class="p-2">{{ $item->program->code }}</td>
                            <td class="p-2">{{ $item->rule_name }}</td>
                            <td class="p-2">{{ $item->measured_value }} · {{ __('progression.bases.' . $item->basis) }}</td>
                            <td class="p-2">{{ $item->from_level }} → {{ $item->to_level }}</td>
                            <td class="p-2">{{ __('progression.outcomes.' . ($item->outcome ?? 'pending')) }}</td>
                            @if ($run->status === 'draft')
                                <td class="p-2"><x-filament::button size="sm" color="gray" wire:click="toggleExclusion({{ $item->id }})">
                                    {{ __('progression.' . ($item->outcome === 'excluded' ? 'include' : 'exclude')) }}
                                </x-filament::button></td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-2">{{ __('progression.no_changes') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        @php($items = $this->runs())
        <x-filament::section>
            <ul class="space-y-3">
                @forelse ($items as $item)
                    <li>
                        <x-filament::link :href="$this->getUrl(['run' => $item->id])">{{ $item->term->code }} · #{{ $item->id }}</x-filament::link>
                        · {{ $item->program?->code ?? __('progression.all_programs') }} · {{ __('progression.statuses.' . $item->status) }}
                    </li>
                @empty
                    <li>{{ __('progression.no_runs') }}</li>
                @endforelse
            </ul>
        </x-filament::section>
    @endif
    {{ $items->links() }}
</x-filament-panels::page>
