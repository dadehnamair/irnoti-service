@extends('layouts.account')

@section('title', 'ویرایش گروه')

@section('content')
    <div class="account-card">
        <div class="account-card__head">
            <h2>ویرایش گروه</h2>
            <a href="{{ route('dashboard.contacts.groups') }}">بازگشت به گروه‌ها</a>
        </div>

        @if ($group->remote_id)
            <p class="auth-sub">این گروه با ملی‌پیامک همگام است؛ تغییر نام در ملی‌پیامک اعمال نمی‌شود.</p>
        @endif

        <form method="POST" action="{{ route('dashboard.contacts.groups.update', $group) }}" class="account-form">
            @csrf @method('PUT')

            <label>
                <span>نام گروه *</span>
                <input type="text" name="name" value="{{ old('name', $group->name) }}" required />
            </label>

            <label>
                <span>توضیحات</span>
                <input type="text" name="description" value="{{ old('description', $group->description) }}" />
            </label>

            <label class="contact-form__check">
                <input type="checkbox" name="show_to_child" value="1" @checked(old('show_to_child', $group->show_to_child)) />
                <span>نمایش به زیرمجموعه‌ها</span>
            </label>

            <div class="row-actions">
                <button type="submit" class="btn btn-primary">ذخیرهٔ تغییرات</button>
                <a href="{{ route('dashboard.contacts.groups') }}" class="btn btn-ghost">انصراف</a>
            </div>
        </form>
    </div>
@endsection
