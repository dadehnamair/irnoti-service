@extends('layouts.account')

@section('title', 'دفترچه تلفن')

@php
    $syncClass = ['synced' => 'is-ok', 'local' => 'is-info', 'error' => 'is-danger'];
@endphp

@section('content')
    <div class="account-card">
        <div class="account-card__head">
            <h2>دفترچه تلفن</h2>
            <span>
                <a href="{{ route('dashboard.contacts.groups') }}">گروه‌ها</a>
                &nbsp;·&nbsp;
                <a href="{{ route('dashboard.contacts.send') }}">ارسال گروهی</a>
            </span>
        </div>

        @unless ($hasPanel)
            <p class="auth-sub">
                پنل پیامک شما هنوز فعال نشده است. مخاطبین فعلاً فقط در سامانه ذخیره می‌شوند و پس از
                فعال‌سازی پنل با {{ sms_provider_label() }} همگام خواهند شد.
            </p>
        @endunless

        <form method="GET" action="{{ route('dashboard.contacts') }}" class="account-filter-form">
            <input type="search" name="keyword" value="{{ $keyword }}" placeholder="جستجوی نام یا شماره…" />
            <select name="group">
                <option value="">همه گروه‌ها ({{ $groups->sum('contacts_count') }})</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" @selected($activeGroup === $group->id)>
                        {{ $group->name }} ({{ $group->contacts_count }})
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">جستجو</button>
        </form>

        <details class="account-details">
            <summary>افزودن مخاطب</summary>
            @include('dashboard.contacts.partials.form', [
                'action' => route('dashboard.contacts.store'),
                'contact' => null,
                'groups' => $groups,
            ])
        </details>

        <div class="account-table-wrap">
            <table class="account-table">
                <thead>
                    <tr>
                        <th>نام</th>
                        <th>موبایل</th>
                        <th>گروه‌ها</th>
                        <th>همگام‌سازی</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($contacts as $contact)
                        <tr>
                            <td>{{ $contact->full_name }}</td>
                            <td dir="ltr">{{ $contact->mobile }}</td>
                            <td>
                                @forelse ($contact->groups as $group)
                                    <span class="account-badge">{{ $group->name }}</span>
                                @empty
                                    <span class="auth-sub">—</span>
                                @endforelse
                            </td>
                            <td>
                                <span class="account-badge {{ $syncClass[$contact->sync_status] ?? '' }}"
                                      @if ($contact->sync_error) title="{{ $contact->sync_error }}" @endif>
                                    {{ $contact->sync_status_label }}
                                </span>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a class="btn btn-ghost btn-sm" href="{{ route('dashboard.contacts.edit', $contact) }}">ویرایش</a>
                                    <form method="POST" action="{{ route('dashboard.contacts.destroy', $contact) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-ghost btn-sm is-danger"
                                                data-confirm="حذف مخاطب"
                                                data-confirm-text="«{{ $contact->full_name }}» از دفترچه تلفن شما حذف می‌شود."
                                                data-confirm-yes="حذف">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="account-table__note">
                            <td colspan="5">هنوز مخاطبی ثبت نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="account-pagination">{{ $contacts->links() }}</div>
    </div>
@endsection
