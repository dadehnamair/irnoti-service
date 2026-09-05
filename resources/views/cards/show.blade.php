<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, follow" />
    <meta name="theme-color" content="{{ $card->theme_color ?: config('theme.primary') }}" />
    <title>{{ $card->title ?: $card->code }}@if ($card->company) — {{ $card->company }} @endif</title>
    <link rel="icon" href="/logo/favicon.png" type="image/png" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet" />

    <style>
        :root { --accent: {{ $card->theme_color ?: config('theme.primary') }}; }
        * { box-sizing: border-box; }
        body {
            margin: 0; font-family: 'Vazirmatn', sans-serif; background: #f3f4f6; color: #1f2937;
            min-height: 100vh; display: flex; justify-content: center; padding: 24px 12px;
        }
        .card { width: 100%; max-width: 420px; background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,.08); }
        .cover { height: 140px; background: var(--accent); background-size: cover; background-position: center; }
        .avatar-wrap { display: flex; justify-content: center; margin-top: -56px; }
        .avatar {
            width: 112px; height: 112px; border-radius: 50%; border: 4px solid #fff; background: #e5e7eb;
            background-size: cover; background-position: center; object-fit: cover;
        }
        .body { padding: 12px 24px 28px; text-align: center; }
        .name { font-size: 1.3rem; font-weight: 700; margin: 8px 0 0; }
        .position { color: #6b7280; margin: 4px 0 0; font-size: .95rem; }
        .bio { margin-top: 14px; color: #374151; line-height: 1.9; font-size: .92rem; white-space: pre-line; }
        .actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-top: 20px; }
        .actions a {
            display: flex; align-items: center; justify-content: center; gap: 6px; padding: 12px 8px;
            border-radius: 12px; background: #f3f4f6; color: #1f2937; text-decoration: none; font-size: .85rem; font-weight: 600;
        }
        .actions a.full { grid-column: 1 / -1; background: var(--accent); color: #fff; }
        .products { margin-top: 24px; text-align: right; }
        .products h3 { font-size: .95rem; color: #6b7280; margin-bottom: 10px; }
        .product { display: flex; align-items: center; gap: 10px; padding: 10px; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 8px; }
        .product img { width: 44px; height: 44px; border-radius: 8px; object-fit: cover; background: #f3f4f6; }
        .product .info { flex: 1; }
        .product .info strong { display: block; font-size: .9rem; }
        .product .info span { color: #6b7280; font-size: .8rem; }
        .footer-brand { text-align: center; margin-top: 22px; font-size: .75rem; color: #9ca3af; }
        .footer-brand a { color: inherit; }
    </style>
</head>

<body>
    <div class="card">
        <div class="cover" @if ($card->cover_path) style="background-image:url('{{ \Illuminate\Support\Facades\Storage::url($card->cover_path) }}')" @endif></div>

        <div class="avatar-wrap">
            <div class="avatar" @if ($card->avatar_path) style="background-image:url('{{ \Illuminate\Support\Facades\Storage::url($card->avatar_path) }}')" @endif></div>
        </div>

        <div class="body">
            <p class="name">{{ $card->title ?: $card->code }}</p>
            @if ($card->position || $card->company)
                <p class="position">{{ trim(($card->position ?: '').($card->position && $card->company ? ' · ' : '').($card->company ?: '')) }}</p>
            @endif

            @if ($card->bio)
                <p class="bio">{{ $card->bio }}</p>
            @endif

            <div class="actions">
                @if ($card->mobile)
                    <a href="tel:{{ $card->mobile }}">📞 تماس</a>
                @endif
                @if ($card->whatsapp)
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $card->whatsapp) }}" target="_blank" rel="noopener">💬 واتساپ</a>
                @endif
                @if ($card->telegram)
                    <a href="https://t.me/{{ ltrim($card->telegram, '@') }}" target="_blank" rel="noopener">✈️ تلگرام</a>
                @endif
                @if ($card->instagram)
                    <a href="https://instagram.com/{{ ltrim($card->instagram, '@') }}" target="_blank" rel="noopener">📷 اینستاگرام</a>
                @endif
                @if ($card->email)
                    <a href="mailto:{{ $card->email }}">✉️ ایمیل</a>
                @endif
                @if ($card->website)
                    <a href="{{ $card->website }}" target="_blank" rel="noopener">🌐 وبسایت</a>
                @endif
                <a class="full" href="?vcf=1">💾 ذخیره مخاطب</a>
            </div>

            @if ($card->address)
                <p class="bio">📍 {{ $card->address }}</p>
            @endif

            @if (!empty($card->products))
                <div class="products">
                    <h3>محصولات و خدمات</h3>
                    @foreach ($card->products as $product)
                        <div class="product">
                            @if (!empty($product['image']))
                                <img src="{{ $product['image'] }}" alt="" />
                            @else
                                <div class="product-img" style="width:44px;height:44px;border-radius:8px;background:#f3f4f6"></div>
                            @endif
                            <div class="info">
                                <strong>{{ $product['title'] ?? '' }}</strong>
                                @if (!empty($product['price']))
                                    <span>{{ number_format((int) $product['price']) }} تومان</span>
                                @endif
                            </div>
                            @if (!empty($product['url']))
                                <a href="{{ $product['url'] }}" target="_blank" rel="noopener" style="font-size:.8rem">مشاهده</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <p class="footer-brand">ساخته‌شده با <a href="{{ config('theme.seo.url') }}">{{ config('theme.brand') }}</a></p>
</body>

</html>
