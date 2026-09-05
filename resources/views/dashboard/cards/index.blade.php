@extends('layouts.account')

@section('title', 'کارت ویزیت دیجیتال')

@section('content')
<div class="account-card">
    <div class="account-card__head">
        <h2>کارت‌های ویزیت دیجیتال من</h2>
        <a class="btn btn-primary" href="{{ route('dashboard.cards.create') }}">+ کارت جدید</a>
    </div>

    @forelse ($cards as $card)
    <div class="account-table-wrap" style="margin-top:16px">
        <table class="account-table">
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $card->title ?: $card->code }}</strong>
                    </td>
                    <td>
                        <div class="auth-sub" dir="ltr">{{ $card->domain->host }}/{{ $card->code }}</div>
                    </td>
                    <td>
                        <span class="account-badge {{ $card->status === 'active' ? 'is-ok' : ($card->status === 'awaiting_payment' ? 'is-warn' : 'is-info') }}">
                            {{ $card->status_label }}
                        </span>
                    </td>
                    <td>
                        <a class="btn btn-ghost" href="{{ route('dashboard.cards.edit', $card) }}">ویرایش</a>
                        @if ($card->status === 'active')
                        <a class="btn btn-ghost" href="{{ $card->public_url }}" target="_blank" rel="noopener">مشاهده</a>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    @empty
    <p class="auth-sub" style="margin-top:16px">هنوز کارت ویزیت دیجیتالی نساخته‌اید.</p>
    @endforelse
</div>
@endsection