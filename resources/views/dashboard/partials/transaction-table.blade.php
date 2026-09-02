{{-- Shared financial-ledger table (docs/starter.md §22). $transactions is a
     Collection or a Paginator; pass $paginated=true to render page links. --}}
@php($paginated = $paginated ?? false)

@if (count($transactions) === 0)
    <p class="auth-sub">هنوز تراکنشی ثبت نشده است.</p>
@else
    <div class="account-table-wrap">
        <table class="account-table">
            <thead>
                <tr>
                    <th>تاریخ</th>
                    <th>نوع</th>
                    <th>شرح</th>
                    <th>مبلغ</th>
                    <th>موجودی پس از تراکنش</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transactions as $tx)
                    <tr>
                        <td dir="ltr">@jdatetime($tx->created_at)</td>
                        <td>{{ $tx->type_label }}</td>
                        <td>{{ $tx->description ?: '—' }}</td>
                        <td class="{{ $tx->direction === 'debit' ? 'is-danger' : 'is-ok' }}">
                            {{ $tx->direction === 'debit' ? '−' : '+' }}@toman($tx->amount) تومان
                        </td>
                        <td>@toman($tx->balance_after) تومان</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($paginated)
        <div class="account-pagination">{{ $transactions->links() }}</div>
    @endif
@endif
