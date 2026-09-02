@extends('layouts.account')

@section('title', 'گروه‌های دفترچه تلفن')

@php
    $syncClass = ['synced' => 'is-ok', 'local' => 'is-info', 'error' => 'is-danger'];
    $pulling = collect($pullingIds ?? []);
@endphp

@section('content')
    <div class="account-card">
        <div class="account-card__head">
            <h2>گروه‌های دفترچه تلفن</h2>
            <a href="{{ route('dashboard.contacts') }}">بازگشت به مخاطبین</a>
        </div>

        @if ($hasPanel)
            <div class="phonebook-toolbar">
                <form method="POST" action="{{ route('dashboard.contacts.import') }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">دریافت گروه‌ها از ملی‌پیامک</button>
                </form>
                <p class="auth-sub">
                    ابتدا گروه‌ها را دریافت کنید؛ سپس روی هر گروه دکمهٔ «دریافت مخاطبین» را بزنید تا مخاطبین همان گروه همگام شوند.
                </p>
            </div>
        @else
            <p class="auth-sub">پنل پیامک شما هنوز فعال نشده است؛ گروه‌ها فعلاً فقط در سامانه ذخیره می‌شوند.</p>
        @endif

        <details class="account-details">
            <summary>ساخت گروه جدید</summary>
            <form method="POST" action="{{ route('dashboard.contacts.groups.store') }}" class="account-form">
                @csrf
                <label>
                    <span>نام گروه *</span>
                    <input type="text" name="name" value="{{ old('name') }}" required />
                </label>
                <label>
                    <span>توضیحات</span>
                    <input type="text" name="description" value="{{ old('description') }}" />
                </label>
                <label class="contact-form__check">
                    <input type="checkbox" name="show_to_child" value="1" @checked(old('show_to_child')) />
                    <span>نمایش به زیرمجموعه‌ها</span>
                </label>
                <button type="submit" class="btn btn-primary">ساخت گروه</button>
            </form>
        </details>

        <div class="account-table-wrap">
            <table class="account-table">
                <thead>
                    <tr>
                        <th>گروه</th>
                        <th>مخاطبین</th>
                        <th>همگام‌سازی</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr>
                            <td>
                                <strong>{{ $group->name }}</strong>
                                @if ($group->description)
                                    <span class="account-cell-sub">{{ $group->description }}</span>
                                @endif
                            </td>
                            <td>
                                <span>{{ number_format($group->contacts_count) }} مخاطب</span>
                                <span class="account-cell-sub">
                                    @if ($group->contacts_synced_at)
                                        آخرین دریافت: {{ jalali_date($group->contacts_synced_at) }}
                                    @else
                                        هنوز دریافت نشده
                                    @endif
                                </span>
                            </td>
                            <td>
                                <span class="account-badge {{ $syncClass[$group->sync_status] ?? '' }}"
                                      @if ($group->sync_error) title="{{ $group->sync_error }}" @endif>
                                    {{ $group->sync_status_label }}
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    @if ($hasPanel && $group->sync_status !== 'synced')
                                        <form method="POST" action="{{ route('dashboard.contacts.groups.sync', $group) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary btn-sm">همگام‌سازی گروه</button>
                                        </form>
                                    @elseif ($hasPanel && $group->remote_id)
                                        @if ($pulling->contains($group->id))
                                            <button type="button" class="btn btn-secondary btn-sm" disabled>در حال دریافت…</button>
                                        @else
                                            <form method="POST" action="{{ route('dashboard.contacts.groups.pull', $group) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary btn-sm">
                                                    {{ $group->contacts_synced_at ? 'به‌روزرسانی مخاطبین' : 'دریافت مخاطبین' }}
                                                </button>
                                            </form>
                                        @endif
                                    @endif

                                    <a class="btn btn-ghost btn-sm" href="{{ route('dashboard.contacts.groups.edit', $group) }}">ویرایش</a>

                                    <form method="POST" action="{{ route('dashboard.contacts.groups.destroy', $group) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-sm is-danger"
                                                data-confirm="حذف گروه «{{ $group->name }}»"
                                                data-confirm-text="گروه از سامانه حذف می‌شود. مخاطبین حذف نمی‌شوند و در ملی‌پیامک هم باقی می‌مانند."
                                                data-confirm-yes="حذف">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="account-table__note">
                            <td colspan="4">هنوز گروهی ساخته نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
