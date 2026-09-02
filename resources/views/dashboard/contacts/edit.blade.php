@extends('layouts.account')

@section('title', 'ویرایش مخاطب')

@section('content')
    <div class="account-card">
        <div class="account-card__head">
            <h2>ویرایش مخاطب</h2>
            <a href="{{ route('dashboard.contacts') }}">بازگشت به دفترچه تلفن</a>
        </div>

        @if ($contact->sync_status === 'error' && $contact->sync_error)
            <p class="auth-sub">آخرین همگام‌سازی با ملی‌پیامک ناموفق بود: {{ $contact->sync_error }}</p>
        @endif

        @include('dashboard.contacts.partials.form', [
            'action' => route('dashboard.contacts.update', $contact),
            'method' => 'PUT',
            'contact' => $contact,
            'groups' => $groups,
        ])
    </div>
@endsection
