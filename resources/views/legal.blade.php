@php
    /** Static legal page (قوانین و مقررات / حریم خصوصی) — body is markdown from the settings table. docs/starter.md §67 */
    $brand = config('theme.brand');
    $primary = config('theme.primary');
    $rendered = \Illuminate\Support\Str::markdown($body ?: '', ['html_input' => 'strip']);
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="{{ $primary }}" />
    <title>{{ $title }} | {{ $brand }}</title>

    <link rel="icon" href="/logo/favicon.png" type="image/png" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ route('theme.css') }}" />
</head>

<body>
    <div class="page-shell">
        @include('partials.site-header')

        <main>
            <section class="section legal-page">
                <div class="container">
                    <h1>{{ $title }}</h1>
                    <div class="legal-body">
                        {!! $rendered !!}
                    </div>
                </div>
            </section>
        </main>

        @include('partials.site-footer')
    </div>

    @includeIf('partials.flash')
</body>

</html>
