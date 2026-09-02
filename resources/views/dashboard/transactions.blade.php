@extends('layouts.account')

@section('title', 'سوابق مالی')

@section('content')
    <div class="account-card">
        <div class="account-card__head">
            <h2>سوابق مالی</h2>
            <a href="{{ route('dashboard.wallet') }}">بازگشت به کیف پول</a>
        </div>

        <div class="account-filter">
            <a href="{{ route('dashboard.transactions') }}" @class(['is-active' => ! $activeType])>همه</a>
            @foreach ($types as $key => $label)
                <a href="{{ route('dashboard.transactions', ['type' => $key]) }}"
                   @class(['is-active' => $activeType === $key])>{{ $label }}</a>
            @endforeach
        </div>

        @include('dashboard.partials.transaction-table', ['transactions' => $transactions, 'paginated' => true])
    </div>
@endsection
