@php
    /**
     * Collects server-side flash messages + validation errors into a JS array
     * (window.__flash) that resources/js/flash.js renders as SweetAlert2 toasts.
     * Include once, just before </body>. Views should no longer print their own
     * flash banners.
     */
    $flash = [];

    $map = [
        'auth_status' => 'success',
        'status' => 'success',
        'success' => 'success',
        'sms_status' => 'success',
        'auth_error' => 'error',
        'error' => 'error',
        'sms_error' => 'error',
        'payment_error' => 'error',
        'gate_notice' => 'warning',
        'warning' => 'warning',
        'info' => 'info',
    ];

    foreach ($map as $key => $type) {
        if (session()->has($key) && filled(session($key)) && ! is_bool(session($key))) {
            $flash[] = ['type' => $type, 'text' => (string) session($key)];
        }
    }

    if (session()->pull('payment_success')) {
        $flash[] = ['type' => 'success', 'text' => 'پرداخت با موفقیت انجام شد.'];
    }

    if (session()->pull('order_created')) {
        $flash[] = ['type' => 'success', 'text' => 'درخواست شما با موفقیت ثبت شد.'];
    }

    if ($errors->any()) {
        $flash[] = [
            'type' => 'error',
            'title' => 'لطفاً خطاهای زیر را برطرف کنید',
            'text' => implode("\n", $errors->all()),
            'modal' => true,
        ];
    }
@endphp

@if (! empty($flash))
    <script>
        window.__flash = (window.__flash || []).concat(@json($flash));
    </script>
@endif
