<?php

namespace App\Http\Controllers;

use App\Models\LineOrder;
use App\Models\SmsLine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LineController extends Controller
{
    /**
     * Public dedicated-lines catalogue ("/lines") — docs/starter.md §9 / §80.
     * Lines are grouped by prefix into tabs; filtering by digits / type / rond
     * happens client-side. Everything is DB-driven (SmsLineResource in admin).
     */
    public function index(): View
    {
        $lines = SmsLine::query()->active()->ordered()->get();

        $groups = $lines->groupBy('prefix')
            ->map(fn ($items, $prefix) => [
                'prefix' => $prefix,
                'label' => 'خطوط '.$prefix,
                'lines' => $items,
            ])
            ->values();

        return view('lines', [
            'groups' => $groups,
            'digitOptions' => $lines->pluck('digits')->unique()->sort()->values(),
            'typeOptions' => $lines->pluck('line_type')->unique()->values(),
        ]);
    }

    /**
     * Capture a purchase request (docs/starter.md §11). No online payment yet —
     * the order lands as "pending" and the admin walks it through the workflow.
     */
    public function order(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sms_line_id' => ['required', Rule::exists('sms_lines', 'id')->where('is_active', true)],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'desired_number' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'customer_name' => 'نام',
            'customer_phone' => 'موبایل',
            'customer_email' => 'ایمیل',
        ]);

        $line = SmsLine::findOrFail($data['sms_line_id']);

        $order = LineOrder::create([
            'sms_line_id' => $line->id,
            'line_label' => trim($line->group_label.' — '.$line->display_number),
            'price' => $line->price,
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'] ?? null,
            'company' => $data['company'] ?? null,
            'desired_number' => $data['desired_number'] ?? null,
            'note' => $data['note'] ?? null,
            'status' => $line->requires_inquiry ? 'pending' : 'awaiting_payment',
        ]);

        return redirect()
            ->route('lines.track', $order)
            ->with('order_created', true);
    }

    /** Public order-status page, keyed by the order token (docs/starter.md §11). */
    public function track(LineOrder $order): View
    {
        return view('line-order', [
            'order' => $order,
            'justCreated' => (bool) session('order_created'),
        ]);
    }
}
