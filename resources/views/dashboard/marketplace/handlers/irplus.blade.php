@php
    /** IrPlus add-on panel (docs/starter.md §15/§17): connection, passenger sync, per-group SMS. */
    $groups = $installation->contactGroups()->withCount('contacts')->orderBy('name')->get();
    $fields = $app->configFields();
@endphp

<div class="account-card">
    <h3>دریافت مسافران از ایرپلاس</h3>
    <p class="auth-sub">لیست مسافران و گروه‌بندی‌های آژانس شما به دفترچه‌تلفن اختصاصی این افزونه اضافه می‌شود.</p>

    <form method="POST" action="{{ route('marketplace.sync', $installation) }}" style="margin-top:12px">
        @csrf
        <button type="submit" class="btn btn-primary">دریافت / به‌روزرسانی مسافران</button>
    </form>
</div>

<div class="account-card">
    <h3>گروه‌های همگام‌شده</h3>

    @if ($groups->isEmpty())
        <p class="auth-sub">هنوز گروهی دریافت نشده است. ابتدا «دریافت مسافران» را بزنید.</p>
    @else
        <table class="account-table">
            <thead><tr><th>گروه</th><th>تعداد مخاطب</th><th>ارسال پیامک</th></tr></thead>
            <tbody>
                @foreach ($groups as $group)
                    <tr>
                        <td>{{ $group->name }}</td>
                        <td>{{ number_format($group->contacts_count) }}</td>
                        <td>
                            <details>
                                <summary class="btn btn-ghost btn-sm">ارسال پیامک به این گروه</summary>
                                <form method="POST" action="{{ route('dashboard.contacts.send.post') }}" style="margin-top:8px">
                                    @csrf
                                    <input type="hidden" name="mode" value="local" />
                                    <input type="hidden" name="groups[]" value="{{ $group->id }}" />
                                    <label class="form-row">
                                        <span>متن پیام</span>
                                        <textarea name="message" rows="3" maxlength="600" required></textarea>
                                    </label>
                                    <button type="submit" class="btn btn-primary btn-sm">ارسال به {{ number_format($group->contacts_count) }} نفر</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="auth-sub" style="margin-top:8px">ارسال گروهی نیازمند فعال بودن پنل پیامک و اعتبار کافی است.</p>
    @endif
</div>

@if ($fields)
    <div class="account-card">
        <h3>اطلاعات اتصال</h3>
        <form method="POST" action="{{ route('marketplace.config', $installation) }}" style="margin-top:12px">
            @csrf
            @foreach ($fields as $field)
                @php $current = data_get($installation->config, $field['key']); @endphp
                <label class="form-row">
                    <span>{{ $field['label'] ?? $field['key'] }}</span>
                    <input
                        type="{{ ! empty($field['secret']) ? 'password' : 'text' }}"
                        name="config[{{ $field['key'] }}]"
                        value="{{ old('config.' . $field['key'], ! empty($field['secret']) ? '' : $current) }}"
                        placeholder="{{ ! empty($field['secret']) && filled($current) ? 'برای تغییر، مقدار جدید وارد کنید' : '' }}"
                        autocomplete="off" />
                    @if (! empty($field['help']))<small class="auth-sub">{{ $field['help'] }}</small>@endif
                </label>
            @endforeach
            <button type="submit" class="btn btn-secondary">ذخیره اطلاعات اتصال</button>
        </form>
    </div>
@endif
