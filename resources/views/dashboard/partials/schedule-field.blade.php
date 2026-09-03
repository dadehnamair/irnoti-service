@php
    /**
     * Optional "schedule this send" field (docs/starter.md §12/§91).
     * Shared by the single/group SMS send form and the messenger campaign form.
     * The visible input is a Jalali (Shamsi) calendar with a time picker; the
     * real value posted to the server is the Gregorian datetime written into the
     * sibling hidden #schedule_at_value input by the datepicker.
     *
     * @var string|null $label  overrides the default field label
     */
    $scheduleOld = old('schedule_at');
@endphp

<label>
    <span>{{ $label ?? 'زمان‌بندی ارسال (اختیاری)' }}</span>
    <input type="text" data-jdp
           data-jdp-target-value-input="#schedule_at_value" data-jdp-target-value-type="gregorian"
           dir="ltr" autocomplete="off" inputmode="numeric" placeholder="۱۴۰۵/۰۶/۱۲ ۱۴:۳۰"
           value="{{ $scheduleOld ? fa_digits(jalali_datetime($scheduleOld)) : '' }}" />
    <input type="hidden" id="schedule_at_value" name="schedule_at" value="{{ $scheduleOld }}" />
    @error('schedule_at') <span class="field-error">{{ $message }}</span> @enderror
</label>
