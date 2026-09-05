<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Support\OperationNotifier;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Public /contact page: office info (config('theme.*')) + a message form. */
class ContactController extends Controller
{
    public function index(): View
    {
        $canonical = route('contact');

        $jsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'خانه', 'item' => rtrim(config('theme.seo.url'), '/').'/'],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'تماس با ما', 'item' => $canonical],
                    ],
                ],
                [
                    '@type' => 'ContactPage',
                    'name' => 'تماس با ما',
                    'url' => $canonical,
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return view('contact', ['jsonLd' => $jsonLd]);
    }

    public function store(Request $request, OperationNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'string', 'regex:/^0?9\d{9}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'subject' => ['nullable', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ], [], [
            'name' => 'نام',
            'mobile' => 'شماره موبایل',
            'email' => 'ایمیل',
            'subject' => 'موضوع',
            'message' => 'پیام',
        ]);

        $contactMessage = ContactMessage::create($data);

        $notifier->contactMessageReceived($contactMessage);

        return redirect()->route('contact')->with('success', 'پیام شما با موفقیت ثبت شد. به‌زودی با شما تماس می‌گیریم.');
    }
}
