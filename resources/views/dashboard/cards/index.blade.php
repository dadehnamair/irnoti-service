@extends('layouts.account')

@section('title', 'کارت ویزیت دیجیتال')

@section('content')
    <div class="account-card">
        <div class="account-card__head">
            <h2>کارت ویزیت دیجیتال</h2>
            <a class="btn btn-primary btn-sm" href="{{ route('dashboard.cards.create') }}">+ کارت جدید</a>
        </div>

        <div class="account-table-wrap">
            <table class="account-table">
                <thead>
                    <tr>
                        <th>لینک</th>
                        <th>نوع</th>
                        <th>وضعیت</th>
                        <th>بازدید</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cards as $card)
                        <tr>
                            <td dir="ltr">{{ $card->domain->host }}/{{ $card->code }}</td>
                            <td>{{ $card->tier_label }}</td>
                            <td>
                                <span class="account-badge {{ $card->status === 'active' ? 'is-ok' : 'is-warn' }}">
                                    {{ $card->status_label }}
                                </span>
                            </td>
                            <td>{{ number_format($card->views_count) }}</td>
                            <td>
                                <div class="row-actions">
                                    @if ($card->status === 'active')
                                        <a class="btn btn-ghost btn-sm" href="{{ $card->public_url }}" target="_blank" rel="noopener">مشاهده</a>
                                    @endif
                                    <a class="btn btn-ghost btn-sm" href="{{ route('dashboard.cards.edit', $card) }}">ویرایش</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="account-table__note">
                            <td colspan="5">هنوز کارت ویزیتی نساخته‌اید.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
