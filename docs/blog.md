# راهنمای بلاگ/مجله (Blog)

> مکمل [starter.md](starter.md) §33. بخش محتوای بازاریابی سایت با
> دسته‌بندی، برچسب، آرشیو، فید RSS و سئوی مستقل برای هر مقاله/دسته/برچسب.

---

## ۱. مفهوم

بلاگ (`/blog`) یک زیرسیستم کاملاً **دیتابیس‌محور** است: مقاله‌ها،
دسته‌بندی‌ها و برچسب‌ها همگی از پنل ادمین مدیریت می‌شوند و هیچ متنی در Blade
هاردکد نیست. هدف اصلی، محتوای بازاریابی/آموزشی برای جذب ترافیک ارگانیک
است، بنابراین سئوی هر صفحه (عنوان/توضیح متا، Open Graph، JSON-LD،
sitemap، RSS) به‌دقت پوشش داده شده.

---

## ۲. مدل داده

- [`BlogCategory`](../app/Models/BlogCategory.php) — `name`, `slug` (خودکار
  از `name`), `description`, `meta_title`, `meta_description`, `sort`,
  `is_visible`. `scopeVisible()`.
- [`BlogTag`](../app/Models/BlogTag.php) — `name`, `slug` (خودکار)،
  `meta_title`, `meta_description`, `og_image` (ستون‌های سئو، مشابه الگوی
  `BlogPost`/`BlogCategory`، افزوده در مهاجرت
  [`2026_09_05_162200_add_seo_fields_to_blog_tags_table`](../database/migrations/2026_09_05_162200_add_seo_fields_to_blog_tags_table.php)).
  اکسسورهای `meta_title_value` (بازگشت به «برچسب: نام»)،
  `meta_description_value` و `og_image_url` مشابه اکسسورهای `BlogPost` عمل
  می‌کنند.
- [`BlogPost`](../app/Models/BlogPost.php) — `blog_category_id`,
  `author_id` (اختیاری، `belongsTo(User)`), `title`, `slug` (خودکار),
  `excerpt`, `body` (Markdown، `getRenderedBodyAttribute()`), `cover_image`,
  `is_published`, `published_at` (خودکار = اکنون هنگام اولین انتشار),
  `meta_title`, `meta_description`, `og_image`, `canonical_url`, `noindex`,
  `views` (شمارندهٔ بازدید سبک، بدون session/dedupe). اکسسورهای
  `meta_title_value`/`meta_description_value` بازگشت به `title`/`excerpt`
  دارند؛ `reading_minutes` بر مبنای ~۱۵۰ کلمه در دقیقه. رابطهٔ
  `tags(): BelongsToMany` از جدول واسط `blog_post_tag`.

---

## ۳. جریان

همهٔ کوئری‌ها و ساخت JSON-LD داخل
[`BlogController`](../app/Http/Controllers/BlogController.php) هستند، نه
Blade — همان قرارداد سراسری کنترلرهای عمومی سایت:

- `index()` — آرشیو صفحه‌بندی‌شدهٔ مقالات منتشرشده (`scopePublished`) +
  سایدبار دسته‌بندی‌های دارای حداقل یک مقاله + `CollectionPage` JSON-LD.
- `category(slug)` — مقالات یک دسته؛ `metaTitle`/`metaDescription` از
  فیلدهای دسته با بازگشت به `'مقالات '.name`/`description`.
- `tag(slug)` — مقالات یک برچسب؛ `metaTitle`/`metaDescription`/`ogImage`
  از فیلدهای سئوی برچسب (`meta_title`/`meta_description`/`og_image_url`)
  با بازگشت به عبارت «پست‌های برچسب‌خورده با {نام برچسب}» و توضیح تولیدی.
- `show(slug)` — مقالهٔ کامل + افزایش `views` + ۳ مقالهٔ مرتبط (اول از همان
  دسته، سپس جدیدترین‌ها برای تکمیل) + `BlogPosting`+`BreadcrumbList` JSON-LD
  دوتایی.
- `feed()` — RSS ۲.۰ برای ۳۰ مقالهٔ اخیر؛ هر `<item>` شامل `<category>`
  (نام دستهٔ مقاله، در صورت وجود) و `<dc:creator>` (نام نویسنده یا برند به
  ‌عنوان بازگشت) است — فضای‌نام `xmlns:dc` روی تگ `<rss>` اعلام شده.

`resources/views/blog/layout.blade.php` عنوان/توضیح متا را با
`' | '.برند` می‌سازد، `hreflang="fa-IR"` (مطابق لندینگ) و
`<link rel="alternate" type="application/rss+xml">` به فید را در `<head>`
قرار می‌دهد.

---

## ۴. سئو

- **متای هر سطح**: مقاله (`meta_title`/`meta_description`/`og_image`/
  `canonical_url`/`noindex`) → دسته (`meta_title`/`meta_description`) →
  برچسب (`meta_title`/`meta_description`/`og_image`، تازه اضافه‌شده).
  همه اختیاری‌اند و در نبود مقدار، کنترلر یک بازگشتِ معنادار می‌سازد.
- **JSON-LD**: `CollectionPage` روی صفحات آرشیو/دسته/برچسب (شامل
  `hasPart` با فهرست `BlogPosting`های صفحهٔ جاری)؛ روی صفحهٔ مقاله دو اسکریپت
  جدا — `BlogPosting` (نویسنده، ناشر، تصویر، `keywords` از برچسب‌ها) و
  `BreadcrumbList` (خانه ← بلاگ ← دسته (در صورت وجود) ← مقاله).
- **Sitemap**: `/sitemap.xml` (در [routes/web.php](../routes/web.php))
  شامل `blog.index` (با `lastmod` = آخرین `updated_at` مقالات منتشرشده، نه
  تاریخ ثابت امروز)، همهٔ `BlogCategory::visible()`، همهٔ `BlogTag` و همهٔ
  `BlogPost::published()` است.
- **RSS**: `/blog/feed` (`blog.feed`) — `<atom:link rel="self">`،
  `language=fa-IR`، `<category>` و `<dc:creator>` در هر آیتم.
- **hreflang**: `fa-IR` هم روی لندینگ (`landing.blade.php`) و هم روی
  لایوت بلاگ.

---

## ۵. Filament

گروه ناوبری «بلاگ»:

- [`BlogPostResource`](../app/Filament/Resources/BlogPosts/BlogPostResource.php)
  — فرم تب‌دار (محتوا + سئو)، آپلود تصویر شاخص/OG.
- [`BlogCategoryResource`](../app/Filament/Resources/BlogCategories/BlogCategoryResource.php)
  — نام/نامک/توضیح + `meta_title`/`meta_description`/`sort`/`is_visible`.
- [`BlogTagResource`](../app/Filament/Resources/BlogTags/BlogTagResource.php)
  — نام/نامک + بخش «متادیتا» با `meta_title`/`meta_description`/`og_image`
  (همان الگوی بخش سئوی `BlogPostResource`).

---

## ۶. تست‌ها

[`tests/Feature/BlogSeoTest.php`](../tests/Feature/BlogSeoTest.php) —
متای اختصاصی صفحهٔ برچسب، حضور برچسب‌ها در sitemap، `lastmod` پویای
`blog.index`، و وجود `<category>`/`<dc:creator>` در فید RSS.
