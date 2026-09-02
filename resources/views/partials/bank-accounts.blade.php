{{-- Company bank accounts shown on the "ثبت فیش" screen (docs/starter.md §22). --}}
@if ($bankAccounts->isNotEmpty())
    <div class="account-card">
        <h3>حساب‌های بانکی {{ config('theme.brand') }}</h3>
        <p class="auth-sub">وجه را به یکی از حساب‌های زیر واریز کنید، سپس مشخصات فیش را ثبت کنید.</p>

        <div class="bank-accounts">
            @foreach ($bankAccounts as $account)
                <div class="bank-account">
                    <div class="bank-account__head">
                        <strong>{{ $account->bank_name }}</strong>
                        <span>{{ $account->owner_name }}</span>
                    </div>
                    <dl>
                        @if ($account->card_display)
                            <div>
                                <dt>شماره کارت</dt>
                                <dd dir="ltr">{{ $account->card_display }}</dd>
                            </div>
                        @endif
                        @if ($account->sheba_display)
                            <div>
                                <dt>شبا</dt>
                                <dd dir="ltr">{{ $account->sheba_display }}</dd>
                            </div>
                        @endif
                        @if ($account->account_number)
                            <div>
                                <dt>شماره حساب</dt>
                                <dd dir="ltr">{{ $account->account_number }}</dd>
                            </div>
                        @endif
                    </dl>
                    @if ($account->note)
                        <p class="account-inline-note">{{ $account->note }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
