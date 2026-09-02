@php
    /**
     * Compact multi-select for phonebook groups — a select-style toggle that
     * opens a scrollable, searchable checklist. Degrades to a plain checklist
     * with no JS. Params: $groups, $selectedGroupIds (iterable of ids),
     * optional $withCounts, $markUnsynced.
     */
    $selectedGroupIds = collect($selectedGroupIds ?? [])->map(fn ($v) => (int) $v);
    $withCounts = $withCounts ?? false;
    $markUnsynced = $markUnsynced ?? false;
    $count = $selectedGroupIds->count();
@endphp

<div class="group-picker" data-group-picker>
    <span class="group-picker__caption">گروه‌ها</span>

    <button type="button" class="group-picker__toggle" data-group-picker-toggle aria-expanded="false">
        <span data-group-picker-label>
            @if ($count === 0)
                انتخاب گروه‌ها
            @elseif ($count === 1)
                {{ optional($groups->firstWhere('id', $selectedGroupIds->first()))->name ?? '۱ گروه' }}
            @else
                {{ $count }} گروه انتخاب شد
            @endif
        </span>
        <svg class="group-picker__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
    </button>

    {{-- No `hidden` here so the checklist still works with JS off; account.js collapses it on load. --}}
    <div class="group-picker__panel" data-group-picker-panel>
        @if ($groups->count() > 6)
            <input type="text" class="group-picker__search" data-group-picker-search
                   placeholder="جستجوی گروه…" autocomplete="off" />
        @endif

        <div class="group-picker__list">
            @forelse ($groups as $group)
                <label class="group-picker__option" data-group-picker-option data-name="{{ $group->name }}">
                    <input type="checkbox" name="groups[]" value="{{ $group->id }}"
                           @checked($selectedGroupIds->contains($group->id))>
                    <span class="group-picker__name">{{ $group->name }}</span>
                    @if ($markUnsynced && ! $group->remote_id)
                        <em class="group-picker__flag">همگام‌نشده</em>
                    @elseif ($withCounts && isset($group->contacts_count))
                        <em class="group-picker__num">{{ $group->contacts_count }}</em>
                    @endif
                </label>
            @empty
                <p class="group-picker__empty">هنوز گروهی نساخته‌اید.</p>
            @endforelse
        </div>
    </div>
</div>
