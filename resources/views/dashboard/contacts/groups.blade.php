@extends('layouts.account')

@section('title', 'گروه‌های دفترچه تلفن')

@php
    $syncClass = ['synced' => 'is-ok', 'local' => 'is-info', 'error' => 'is-danger'];
@endphp

@section('content')
    <div class="account-card">
        <div class="account-card__head">
            <h2>گروه‌های دفترچه تلفن</h2>
            <a href="{{ route('dashboard.contacts') }}">بازگشت به مخاطبین</a>
        </div>

        @if ($hasPanel)
            <form method="POST" action="{{ route('dashboard.contacts.import') }}"
                  onsubmit="return confirm('گروه‌ها و مخاطبین از پنل ملی‌پیامک شما دریافت و در سامانه ذخیره شوند؟')">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">دریافت از ملی‌پیامک</button>
            </form>
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
                        <th>نام</th>
                        <th>توضیحات</th>
                        <th>مخاطبین</th>
                        <th>همگام‌سازی</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr>
                            <td>{{ $group->name }}</td>
                            <td>{{ $group->description ?: '—' }}</td>
                            <td>{{ number_format($group->contacts_count) }}</td>
                            <td>
                                <span class="account-badge {{ $syncClass[$group->sync_status] ?? '' }}"
                                      @if ($group->sync_error) title="{{ $group->sync_error }}" @endif>
                                    {{ $group->sync_status_label }}
                                </span>
                            </td>
                            <td class="contact-actions">
                                <details class="account-inline-edit">
                                    <summary class="btn btn-secondary btn-sm">ویرایش</summary>
                                    <form method="POST" action="{{ route('dashboard.contacts.groups.update', $group) }}" class="account-form">
                                        @csrf @method('PUT')
                                        <label><span>نام</span><input type="text" name="name" value="{{ $group->name }}" required /></label>
                                        <label><span>توضیحات</span><input type="text" name="description" value="{{ $group->description }}" /></label>
                                        <label class="contact-form__check">
                                            <input type="checkbox" name="show_to_child" value="1" @checked($group->show_to_child) />
                                            <span>نمایش به زیرمجموعه‌ها</span>
                                        </label>
                                        <button type="submit" class="btn btn-primary btn-sm">ذخیره</button>
                                    </form>
                                </details>

                                @if ($hasPanel && $group->sync_status !== 'synced')
                                    <form method="POST" action="{{ route('dashboard.contacts.groups.sync', $group) }}" style="display:inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm">همگام‌سازی</button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('dashboard.contacts.groups.destroy', $group) }}"
                                      onsubmit="return confirm('این گروه حذف شود؟ مخاطبین حذف نمی‌شوند.')" style="display:inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr class="account-table__note">
                            <td colspan="5">هنوز گروهی ساخته نشده است.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
