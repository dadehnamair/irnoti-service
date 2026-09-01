<?php

namespace Database\Seeders;

use App\Models\DocArticle;
use App\Models\DocCategory;
use Illuminate\Database\Seeder;

class DocsSeeder extends Seeder
{
    /**
     * Seed a starter set of API documentation matching docs/starter.md §34.
     * Everything here is editable from the Filament admin panel afterwards —
     * this only gives the /developers page real content to launch with.
     *
     * Safe to re-run: categories/articles are matched by slug and updated.
     */
    public function run(): void
    {
        foreach ($this->categories() as $sort => $data) {
            $category = DocCategory::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'icon' => $data['icon'] ?? null,
                    'sort' => $sort,
                    'is_published' => true,
                ],
            );

            foreach ($data['articles'] as $articleSort => $article) {
                $model = DocArticle::updateOrCreate(
                    ['doc_category_id' => $category->id, 'slug' => $article['slug']],
                    [
                        'title' => $article['title'],
                        'excerpt' => $article['excerpt'] ?? null,
                        'body' => $article['body'] ?? null,
                        'http_method' => $article['method'] ?? null,
                        'endpoint' => $article['endpoint'] ?? null,
                        'sort' => $articleSort,
                        'is_published' => true,
                    ],
                );

                $model->parameters()->delete();
                foreach ($article['parameters'] ?? [] as $paramSort => $param) {
                    $model->parameters()->create([
                        'name' => $param[0],
                        'type' => $param[1],
                        'is_required' => $param[2],
                        'description' => $param[3],
                        'example' => $param[4] ?? null,
                        'sort' => $paramSort,
                    ]);
                }

                $model->codeSamples()->delete();
                foreach ($article['code'] ?? [] as $codeSort => $code) {
                    $model->codeSamples()->create([
                        'language' => $code[0],
                        'label' => $code[1] ?? null,
                        'code' => $code[2],
                        'sort' => $codeSort,
                    ]);
                }
            }
        }
    }

    private function categories(): array
    {
        return [
            [
                'slug' => 'overview',
                'title' => 'مرور کلی',
                'description' => 'شروع کار با API، آدرس پایه و احراز هویت.',
                'icon' => 'heroicon-o-book-open',
                'articles' => [
                    [
                        'slug' => 'introduction',
                        'title' => 'معرفی API',
                        'excerpt' => 'آدرس پایه، فرمت داده‌ها و اصول کلی کار با API پیامک irnoti.',
                        'body' => <<<'MD'
        API پیامک **irnoti** یک وب‌سرویس REST است. تمام درخواست‌ها روی HTTPS انجام می‌شوند و
        بدنه و پاسخ‌ها با فرمت JSON رد و بدل می‌شوند.

        ## آدرس پایه

        ```
        https://api.irnoti.com/v1
        ```

        ## قالب پاسخ

        همهٔ پاسخ‌ها ساختار یکسانی دارند:

        ```json
        {
          "success": true,
          "data": { },
          "message": null
        }
        ```

        در صورت بروز خطا، `success` برابر `false` است و کد و توضیح خطا در `message`
        و `error_code` برمی‌گردد. فهرست کامل کدها در بخش «کدهای خطا» آمده است.

        ## محدودیت نرخ درخواست

        هر کلید API به‌صورت پیش‌فرض تا **۶۰ درخواست در دقیقه** را می‌پذیرد. در صورت عبور،
        پاسخ `429 Too Many Requests` دریافت می‌کنید.
        MD,
                    ],
                    [
                        'slug' => 'authentication',
                        'title' => 'احراز هویت',
                        'excerpt' => 'ارسال کلید API در هدر Authorization به‌صورت Bearer Token.',
                        'method' => 'GET',
                        'endpoint' => '/api/v1/account',
                        'body' => <<<'MD'
        برای احراز هویت، کلید API خود را (از پنل کاربری، بخش «کلیدهای API») در هدر
        `Authorization` و به‌صورت Bearer Token ارسال کنید:

        ```
        Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx
        ```

        کلید Secret فقط یک بار هنگام ساخت نمایش داده می‌شود؛ آن را در جای امن نگه دارید.
        برای تست می‌توانید از کلید `sk_test_...` استفاده کنید که پیامک واقعی ارسال نمی‌کند.

        نمونهٔ زیر اطلاعات حساب و اعتبار فعلی را برمی‌گرداند و برای بررسی صحت کلید مناسب است.
        MD,
                        'code' => [
                            ['curl', null, <<<'SH'
        curl https://api.irnoti.com/v1/account \
          -H "Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx"
        SH],
                            ['php', null, <<<'PHP'
        <?php

        $ch = curl_init('https://api.irnoti.com/v1/account');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx',
            ],
        ]);

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        print_r($response);
        PHP],
                            ['javascript', null, <<<'JS'
        const res = await fetch('https://api.irnoti.com/v1/account', {
          headers: { Authorization: 'Bearer sk_live_xxxxxxxxxxxxxxxx' },
        });

        const account = await res.json();
        console.log(account);
        JS],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'sms',
                'title' => 'ارسال پیامک',
                'description' => 'ارسال تکی، گروهی و متناظر.',
                'icon' => 'heroicon-o-paper-airplane',
                'articles' => [
                    [
                        'slug' => 'send-single',
                        'title' => 'ارسال پیامک تکی',
                        'excerpt' => 'ارسال یک پیامک به یک شماره از یک خط مشخص.',
                        'method' => 'POST',
                        'endpoint' => '/api/v1/sms/send',
                        'body' => <<<'MD'
        یک پیامک متنی از خط انتخابی شما به یک گیرنده ارسال می‌کند. در پاسخ، شناسهٔ ارسال
        (`message_id`) برمی‌گردد که برای پیگیری وضعیت تحویل استفاده می‌شود.
        MD,
                        'parameters' => [
                            ['sender', 'string', true, 'شماره خط ارسال‌کننده (خط اختصاصی یا اشتراکی فعال روی حساب شما).', '5000XXXX'],
                            ['recipient', 'string', true, 'شمارهٔ موبایل گیرنده با فرمت ۰۹xxxxxxxxx یا +989xxxxxxxxx.', '09123456789'],
                            ['message', 'string', true, 'متن پیامک. طول مجاز بر اساس تعداد کاراکتر و نوع (فارسی/انگلیسی) محاسبه می‌شود.', 'سفارش شما ثبت شد.'],
                            ['schedule_at', 'string (ISO 8601)', false, 'در صورت ارسال، پیامک در زمان مشخص‌شده ارسال می‌شود.', '2026-03-21T09:30:00+03:30'],
                        ],
                        'code' => [
                            ['curl', null, <<<'SH'
        curl -X POST https://api.irnoti.com/v1/sms/send \
          -H "Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx" \
          -H "Content-Type: application/json" \
          -d '{
            "sender": "5000XXXX",
            "recipient": "09123456789",
            "message": "خوش آمدید، سفارش شما ثبت شد."
          }'
        SH],
                            ['php', 'PHP (cURL)', <<<'PHP'
        <?php

        $payload = [
            'sender' => '5000XXXX',
            'recipient' => '09123456789',
            'message' => 'خوش آمدید، سفارش شما ثبت شد.',
        ];

        $ch = curl_init('https://api.irnoti.com/v1/sms/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx',
                'Content-Type: application/json',
            ],
        ]);

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        echo $response['data']['message_id'] ?? $response['message'];
        PHP],
                            ['laravel', 'Laravel (HTTP Client)', <<<'PHP'
        use Illuminate\Support\Facades\Http;

        $response = Http::withToken('sk_live_xxxxxxxxxxxxxxxx')
            ->acceptJson()
            ->post('https://api.irnoti.com/v1/sms/send', [
                'sender' => '5000XXXX',
                'recipient' => '09123456789',
                'message' => 'خوش آمدید، سفارش شما ثبت شد.',
            ]);

        $messageId = $response->json('data.message_id');
        PHP],
                            ['javascript', 'Node.js / Fetch', <<<'JS'
        const res = await fetch('https://api.irnoti.com/v1/sms/send', {
          method: 'POST',
          headers: {
            Authorization: 'Bearer sk_live_xxxxxxxxxxxxxxxx',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            sender: '5000XXXX',
            recipient: '09123456789',
            message: 'خوش آمدید، سفارش شما ثبت شد.',
          }),
        });

        const { data } = await res.json();
        console.log(data.message_id);
        JS],
                            ['python', null, <<<'PY'
        import requests

        response = requests.post(
            "https://api.irnoti.com/v1/sms/send",
            headers={"Authorization": "Bearer sk_live_xxxxxxxxxxxxxxxx"},
            json={
                "sender": "5000XXXX",
                "recipient": "09123456789",
                "message": "خوش آمدید، سفارش شما ثبت شد.",
            },
        )

        print(response.json()["data"]["message_id"])
        PY],
                            ['csharp', null, <<<'CS'
        using var client = new HttpClient();
        client.DefaultRequestHeaders.Authorization =
            new AuthenticationHeaderValue("Bearer", "sk_live_xxxxxxxxxxxxxxxx");

        var payload = new
        {
            sender = "5000XXXX",
            recipient = "09123456789",
            message = "خوش آمدید، سفارش شما ثبت شد."
        };

        var response = await client.PostAsJsonAsync(
            "https://api.irnoti.com/v1/sms/send", payload);

        Console.WriteLine(await response.Content.ReadAsStringAsync());
        CS],
                            ['java', null, <<<'JAVA'
        var body = """
            {"sender":"5000XXXX","recipient":"09123456789","message":"خوش آمدید"}
            """;

        var request = HttpRequest.newBuilder()
            .uri(URI.create("https://api.irnoti.com/v1/sms/send"))
            .header("Authorization", "Bearer sk_live_xxxxxxxxxxxxxxxx")
            .header("Content-Type", "application/json")
            .POST(HttpRequest.BodyPublishers.ofString(body))
            .build();

        var response = HttpClient.newHttpClient()
            .send(request, HttpResponse.BodyHandlers.ofString());

        System.out.println(response.body());
        JAVA],
                        ],
                    ],
                    [
                        'slug' => 'send-bulk',
                        'title' => 'ارسال گروهی',
                        'excerpt' => 'ارسال یک متن واحد به فهرستی از شماره‌ها در یک درخواست.',
                        'method' => 'POST',
                        'endpoint' => '/api/v1/sms/send-bulk',
                        'body' => <<<'MD'
        یک متن را به‌صورت هم‌زمان برای چند گیرنده ارسال می‌کند. شماره‌های نامعتبر و تکراری
        پیش از ارسال حذف می‌شوند و در پاسخ، تعداد پذیرفته‌شده و شناسهٔ دسته (`batch_id`) برمی‌گردد.
        حداکثر ۱۰۰۰ شماره در هر درخواست.
        MD,
                        'parameters' => [
                            ['sender', 'string', true, 'شماره خط ارسال‌کننده.', '5000XXXX'],
                            ['recipients', 'string[]', true, 'آرایه‌ای از شماره‌های موبایل.', '["09120000000","09120000001"]'],
                            ['message', 'string', true, 'متن پیامک مشترک برای همهٔ گیرندگان.', 'جشنواره فروش آغاز شد.'],
                        ],
                        'code' => [
                            ['curl', null, <<<'SH'
        curl -X POST https://api.irnoti.com/v1/sms/send-bulk \
          -H "Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx" \
          -H "Content-Type: application/json" \
          -d '{
            "sender": "5000XXXX",
            "recipients": ["09120000000", "09120000001", "09120000002"],
            "message": "جشنواره فروش آغاز شد."
          }'
        SH],
                            ['php', null, <<<'PHP'
        <?php

        $payload = [
            'sender' => '5000XXXX',
            'recipients' => ['09120000000', '09120000001', '09120000002'],
            'message' => 'جشنواره فروش آغاز شد.',
        ];

        $ch = curl_init('https://api.irnoti.com/v1/sms/send-bulk');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx',
                'Content-Type: application/json',
            ],
        ]);

        print_r(json_decode(curl_exec($ch), true));
        curl_close($ch);
        PHP],
                            ['javascript', null, <<<'JS'
        const res = await fetch('https://api.irnoti.com/v1/sms/send-bulk', {
          method: 'POST',
          headers: {
            Authorization: 'Bearer sk_live_xxxxxxxxxxxxxxxx',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            sender: '5000XXXX',
            recipients: ['09120000000', '09120000001', '09120000002'],
            message: 'جشنواره فروش آغاز شد.',
          }),
        });

        console.log(await res.json());
        JS],
                        ],
                    ],
                    [
                        'slug' => 'send-pair',
                        'title' => 'ارسال متناظر',
                        'excerpt' => 'ارسال متن‌های متفاوت به شماره‌های متفاوت به‌صورت زوج (نظیر به نظیر).',
                        'method' => 'POST',
                        'endpoint' => '/api/v1/sms/send-pair',
                        'body' => <<<'MD'
        وقتی هر گیرنده باید پیام مخصوص خودش را دریافت کند (مثلاً کد پیگیری سفارش)، از ارسال
        متناظر استفاده کنید. آرایهٔ `items` شامل زوج‌های `recipient` و `message` است.
        MD,
                        'parameters' => [
                            ['sender', 'string', true, 'شماره خط ارسال‌کننده.', '5000XXXX'],
                            ['items', 'object[]', true, 'آرایه‌ای از اشیاء شامل recipient و message.', '[{"recipient":"0912...","message":"..."}]'],
                        ],
                        'code' => [
                            ['curl', null, <<<'SH'
        curl -X POST https://api.irnoti.com/v1/sms/send-pair \
          -H "Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx" \
          -H "Content-Type: application/json" \
          -d '{
            "sender": "5000XXXX",
            "items": [
              { "recipient": "09120000000", "message": "کد پیگیری شما: 4181" },
              { "recipient": "09120000001", "message": "کد پیگیری شما: 9920" }
            ]
          }'
        SH],
                            ['php', null, <<<'PHP'
        <?php

        $payload = [
            'sender' => '5000XXXX',
            'items' => [
                ['recipient' => '09120000000', 'message' => 'کد پیگیری شما: 4181'],
                ['recipient' => '09120000001', 'message' => 'کد پیگیری شما: 9920'],
            ],
        ];

        $ch = curl_init('https://api.irnoti.com/v1/sms/send-pair');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx',
                'Content-Type: application/json',
            ],
        ]);

        print_r(json_decode(curl_exec($ch), true));
        curl_close($ch);
        PHP],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'pattern',
                'title' => 'پیامک پترن',
                'description' => 'ارسال پیامک خدماتی و کد یک‌بارمصرف با الگوی تأییدشده.',
                'icon' => 'heroicon-o-rectangle-group',
                'articles' => [
                    [
                        'slug' => 'send-pattern',
                        'title' => 'ارسال پیامک پترن (OTP)',
                        'excerpt' => 'ارسال پیامک با الگوی از پیش تأییدشده و مقادیر متغیر.',
                        'method' => 'POST',
                        'endpoint' => '/api/v1/pattern/send',
                        'body' => <<<'MD'
        پیامک پترن برای ارسال پیام‌های خدماتی مانند کد ورود، تأیید سفارش و اطلاع‌رسانی
        استفاده می‌شود و روی خطوط خدماتی حتی در سامانهٔ «۱۰۸۸» (مزاحم) هم تحویل داده می‌شود.

        ابتدا الگو را از پنل کاربری ثبت و تأیید بگیرید؛ سپس `pattern_code` و مقادیر متغیرها
        را ارسال کنید. کلید‌های `variables` باید دقیقاً با نام متغیرهای الگو یکی باشند.
        MD,
                        'parameters' => [
                            ['sender', 'string', true, 'خط خدماتی مجاز برای پترن.', '3000XXXX'],
                            ['recipient', 'string', true, 'شمارهٔ موبایل گیرنده.', '09123456789'],
                            ['pattern_code', 'string', true, 'کد الگوی تأییدشده.', 'otp_login'],
                            ['variables', 'object', true, 'نگاشت نام متغیر به مقدار.', '{"code":"48213"}'],
                        ],
                        'code' => [
                            ['curl', null, <<<'SH'
        curl -X POST https://api.irnoti.com/v1/pattern/send \
          -H "Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx" \
          -H "Content-Type: application/json" \
          -d '{
            "sender": "3000XXXX",
            "recipient": "09123456789",
            "pattern_code": "otp_login",
            "variables": { "code": "48213" }
          }'
        SH],
                            ['php', null, <<<'PHP'
        <?php

        $payload = [
            'sender' => '3000XXXX',
            'recipient' => '09123456789',
            'pattern_code' => 'otp_login',
            'variables' => ['code' => '48213'],
        ];

        $ch = curl_init('https://api.irnoti.com/v1/pattern/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx',
                'Content-Type: application/json',
            ],
        ]);

        print_r(json_decode(curl_exec($ch), true));
        curl_close($ch);
        PHP],
                            ['laravel', null, <<<'PHP'
        use Illuminate\Support\Facades\Http;

        $messageId = Http::withToken('sk_live_xxxxxxxxxxxxxxxx')
            ->post('https://api.irnoti.com/v1/pattern/send', [
                'sender' => '3000XXXX',
                'recipient' => '09123456789',
                'pattern_code' => 'otp_login',
                'variables' => ['code' => (string) random_int(10000, 99999)],
            ])
            ->json('data.message_id');
        PHP],
                            ['javascript', null, <<<'JS'
        const res = await fetch('https://api.irnoti.com/v1/pattern/send', {
          method: 'POST',
          headers: {
            Authorization: 'Bearer sk_live_xxxxxxxxxxxxxxxx',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            sender: '3000XXXX',
            recipient: '09123456789',
            pattern_code: 'otp_login',
            variables: { code: '48213' },
          }),
        });

        console.log(await res.json());
        JS],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'status',
                'title' => 'وضعیت و اعتبار',
                'description' => 'پیگیری وضعیت تحویل پیامک و مشاهدهٔ اعتبار حساب.',
                'icon' => 'heroicon-o-chart-bar',
                'articles' => [
                    [
                        'slug' => 'delivery',
                        'title' => 'وضعیت تحویل',
                        'excerpt' => 'دریافت وضعیت تحویل یک پیامک با شناسهٔ ارسال.',
                        'method' => 'GET',
                        'endpoint' => '/api/v1/sms/{message_id}/status',
                        'body' => <<<'MD'
        وضعیت لحظه‌ای یک پیامک را برمی‌گرداند. مقدار `status` یکی از این‌هاست:

        | مقدار | معنی |
        | --- | --- |
        | `pending` | در صف ارسال |
        | `sent` | تحویل به مخابرات |
        | `delivered` | تحویل به گوشی گیرنده |
        | `failed` | ناموفق |
        | `rejected` | رد شده (فیلتر محتوا یا شماره نامعتبر) |
        MD,
                        'parameters' => [
                            ['message_id', 'string (path)', true, 'شناسهٔ ارسال که هنگام ارسال دریافت کرده‌اید.', 'msg_9f2c1a'],
                        ],
                        'code' => [
                            ['curl', null, <<<'SH'
        curl https://api.irnoti.com/v1/sms/msg_9f2c1a/status \
          -H "Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx"
        SH],
                            ['php', null, <<<'PHP'
        <?php

        $messageId = 'msg_9f2c1a';

        $ch = curl_init("https://api.irnoti.com/v1/sms/{$messageId}/status");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx'],
        ]);

        $status = json_decode(curl_exec($ch), true)['data']['status'] ?? null;
        curl_close($ch);

        echo $status;
        PHP],
                        ],
                    ],
                    [
                        'slug' => 'credit',
                        'title' => 'دریافت اعتبار',
                        'excerpt' => 'مشاهدهٔ اعتبار ریالی و تعداد پیامک باقی‌مانده.',
                        'method' => 'GET',
                        'endpoint' => '/api/v1/account/credit',
                        'body' => <<<'MD'
        اعتبار فعلی حساب را برمی‌گرداند:

        ```json
        {
          "success": true,
          "data": { "rial": 1250000, "sms_count": 8450 }
        }
        ```
        MD,
                        'code' => [
                            ['curl', null, <<<'SH'
        curl https://api.irnoti.com/v1/account/credit \
          -H "Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx"
        SH],
                            ['php', null, <<<'PHP'
        <?php

        $ch = curl_init('https://api.irnoti.com/v1/account/credit');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx'],
        ]);

        print_r(json_decode(curl_exec($ch), true)['data']);
        curl_close($ch);
        PHP],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'contacts',
                'title' => 'دفترچه تلفن',
                'description' => 'مدیریت گروه‌ها و مخاطبین از طریق API.',
                'icon' => 'heroicon-o-users',
                'articles' => [
                    [
                        'slug' => 'manage-contacts',
                        'title' => 'گروه‌ها و مخاطبین',
                        'excerpt' => 'ساخت گروه، افزودن مخاطب و ارسال بر اساس گروه.',
                        'method' => 'POST',
                        'endpoint' => '/api/v1/contacts',
                        'body' => <<<'MD'
        می‌توانید مخاطبین را در گروه‌ها سازمان‌دهی کنید و هنگام ارسال گروهی به‌جای فهرست
        شماره، `group_id` را بفرستید.

        - `POST /api/v1/contact-groups` — ساخت گروه
        - `POST /api/v1/contacts` — افزودن مخاطب به گروه
        - `GET /api/v1/contact-groups/{id}/contacts` — فهرست مخاطبین گروه
        - `DELETE /api/v1/contacts/{id}` — حذف مخاطب
        MD,
                        'parameters' => [
                            ['group_id', 'integer', true, 'شناسهٔ گروهی که مخاطب به آن اضافه می‌شود.', '12'],
                            ['name', 'string', false, 'نام مخاطب.', 'مریم رضایی'],
                            ['mobile', 'string', true, 'شمارهٔ موبایل مخاطب.', '09123456789'],
                        ],
                        'code' => [
                            ['curl', null, <<<'SH'
        curl -X POST https://api.irnoti.com/v1/contacts \
          -H "Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx" \
          -H "Content-Type: application/json" \
          -d '{ "group_id": 12, "name": "مریم رضایی", "mobile": "09123456789" }'
        SH],
                            ['php', null, <<<'PHP'
        <?php

        $payload = ['group_id' => 12, 'name' => 'مریم رضایی', 'mobile' => '09123456789'];

        $ch = curl_init('https://api.irnoti.com/v1/contacts');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer sk_live_xxxxxxxxxxxxxxxx',
                'Content-Type: application/json',
            ],
        ]);

        print_r(json_decode(curl_exec($ch), true));
        curl_close($ch);
        PHP],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'webhook',
                'title' => 'Webhook',
                'description' => 'دریافت رویدادهای تحویل و پاسخ به‌صورت لحظه‌ای.',
                'icon' => 'heroicon-o-bolt',
                'articles' => [
                    [
                        'slug' => 'configure-webhook',
                        'title' => 'پیکربندی Webhook',
                        'excerpt' => 'ثبت آدرس Webhook و ساختار رویدادهای ارسالی.',
                        'body' => <<<'MD'
        آدرس Webhook را از پنل کاربری (بخش «Webhookها») ثبت کنید. با وقوع هر رویداد،
        یک درخواست `POST` با بدنهٔ JSON به آدرس شما ارسال می‌شود:

        ```json
        {
          "event": "sms.delivered",
          "message_id": "msg_9f2c1a",
          "recipient": "09123456789",
          "occurred_at": "2026-03-21T09:31:04+03:30"
        }
        ```

        رویدادهای موجود: `sms.sent`، `sms.delivered`، `sms.failed`، `sms.inbound`،
        `payment.completed`.

        ## بررسی صحت امضا

        هر درخواست دارای هدر `X-Irnoti-Signature` است که مقدار
        `HMAC-SHA256(body, webhook_secret)` را در خود دارد. پیش از پردازش، این امضا را
        بررسی کنید.
        MD,
                        'code' => [
                            ['php', 'اعتبارسنجی امضا (PHP)', <<<'PHP'
        <?php

        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_IRNOTI_SIGNATURE'] ?? '';
        $expected = hash_hmac('sha256', $payload, getenv('IRNOTI_WEBHOOK_SECRET'));

        if (! hash_equals($expected, $signature)) {
            http_response_code(401);
            exit;
        }

        $event = json_decode($payload, true);
        // ... پردازش رویداد
        http_response_code(200);
        PHP],
                            ['laravel', 'کنترلر Laravel', <<<'PHP'
        public function handle(Request $request)
        {
            $expected = hash_hmac(
                'sha256',
                $request->getContent(),
                config('services.irnoti.webhook_secret'),
            );

            abort_unless(
                hash_equals($expected, $request->header('X-Irnoti-Signature', '')),
                401,
            );

            match ($request->input('event')) {
                'sms.delivered' => /* ... */ null,
                'sms.failed' => /* ... */ null,
                default => null,
            };

            return response()->noContent();
        }
        PHP],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'errors',
                'title' => 'کدهای خطا',
                'description' => 'فهرست کدهای خطا و معنی آن‌ها.',
                'icon' => 'heroicon-o-exclamation-triangle',
                'articles' => [
                    [
                        'slug' => 'error-codes',
                        'title' => 'جدول کدهای خطا',
                        'excerpt' => 'کد وضعیت HTTP، error_code و راه‌حل هر خطا.',
                        'body' => <<<'MD'
        هنگام بروز خطا، بدنهٔ پاسخ به این شکل است:

        ```json
        {
          "success": false,
          "error_code": "insufficient_credit",
          "message": "اعتبار حساب برای این ارسال کافی نیست."
        }
        ```

        | HTTP | error_code | توضیح |
        | --- | --- | --- |
        | 401 | `unauthorized` | کلید API نامعتبر یا ارسال‌نشده است. |
        | 403 | `sender_not_allowed` | خط انتخابی روی این حساب فعال نیست. |
        | 403 | `pattern_not_approved` | الگوی پترن هنوز تأیید نشده است. |
        | 422 | `invalid_recipient` | شمارهٔ گیرنده معتبر نیست. |
        | 422 | `message_too_long` | طول متن از حد مجاز بیشتر است. |
        | 402 | `insufficient_credit` | اعتبار کافی نیست. |
        | 429 | `rate_limited` | تعداد درخواست بیش از حد مجاز است. |
        | 500 | `provider_error` | خطای موقت سرویس‌دهنده؛ چند لحظه بعد دوباره تلاش کنید. |
        MD,
                    ],
                ],
            ],
        ];
    }
}
