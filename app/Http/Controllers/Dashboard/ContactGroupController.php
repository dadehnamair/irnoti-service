<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\ImportGroupContactsJob;
use App\Models\ContactGroup;
use App\Models\Setting;
use App\Services\Sms\SmsPanelNotConfiguredException;
use App\Support\PhonebookSync;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Customer phonebook — group management (docs/starter.md §17). Groups are
 * mirrored to the customer's own Melipayamak panel by {@see PhonebookSync}.
 * Melipayamak has no rename/delete for groups, so those stay local-only and the
 * UI says so.
 */
class ContactGroupController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureEnabled();

        $user = $request->user();

        $groups = $user->contactGroups()->ordered()->withCount('contacts')->get();

        return view('dashboard.contacts.groups', [
            'groups' => $groups,
            'hasPanel' => $user->hasSmsPanel(),
            'pullingIds' => $groups
                ->filter(fn (ContactGroup $g) => (bool) Cache::get(ImportGroupContactsJob::lockKey($g->id)))
                ->pluck('id')
                ->all(),
        ]);
    }

    public function edit(Request $request, ContactGroup $group): View
    {
        $this->ensureEnabled();
        abort_unless($group->user_id === $request->user()->id, 403);

        return view('dashboard.contacts.group-edit', ['group' => $group]);
    }

    public function store(Request $request, PhonebookSync $sync): RedirectResponse
    {
        $this->ensureEnabled();

        $data = $this->validated($request);

        $group = $request->user()->contactGroups()->create($data);
        $sync->pushGroup($group->fresh());

        return redirect()->route('dashboard.contacts.groups')
            ->with($group->fresh()->sync_status === 'error' ? 'warning' : 'status', $this->flash($group->fresh(), 'گروه ساخته شد.'));
    }

    public function update(Request $request, ContactGroup $group): RedirectResponse
    {
        $this->ensureEnabled();
        abort_unless($group->user_id === $request->user()->id, 403);

        $group->update($this->validated($request));

        return redirect()->route('dashboard.contacts.groups')->with(
            'status',
            $group->remote_id
                ? 'گروه ویرایش شد. توجه: تغییر نام گروه در ملی‌پیامک اعمال نمی‌شود.'
                : 'گروه ویرایش شد.',
        );
    }

    /** Queue a pull of this one group's contacts from Melipayamak. */
    public function pullContacts(Request $request, ContactGroup $group): RedirectResponse
    {
        $this->ensureEnabled();
        abort_unless($group->user_id === $request->user()->id, 403);

        if (! $request->user()->hasSmsPanel()) {
            return redirect()->route('dashboard.contacts.groups')
                ->with('error', 'پنل پیامک شما هنوز فعال نشده است.');
        }

        if (! $group->remote_id) {
            return redirect()->route('dashboard.contacts.groups')
                ->with('warning', 'ابتدا این گروه را با ملی‌پیامک همگام کنید.');
        }

        $lock = ImportGroupContactsJob::lockKey($group->id);

        if (Cache::get($lock)) {
            return redirect()->route('dashboard.contacts.groups')
                ->with('warning', 'دریافت مخاطبین این گروه در حال انجام است.');
        }

        Cache::put($lock, true, now()->addMinutes(30));
        ImportGroupContactsJob::dispatch($group->id);

        return redirect()->route('dashboard.contacts.groups')->with(
            'status',
            'دریافت مخاطبین گروه «'.$group->name.'» آغاز شد. بسته به تعداد مخاطبین ممکن است چند دقیقه طول بکشد.',
        );
    }

    public function destroy(Request $request, ContactGroup $group): RedirectResponse
    {
        $this->ensureEnabled();
        abort_unless($group->user_id === $request->user()->id, 403);

        $wasSynced = (bool) $group->remote_id;
        $group->contacts()->detach();
        $group->delete();

        return redirect()->route('dashboard.contacts.groups')->with(
            'status',
            $wasSynced
                ? 'گروه از سیستم حذف شد. توجه: ملی‌پیامک امکان حذف گروه را ندارد و گروه در پنل شما باقی می‌ماند.'
                : 'گروه حذف شد.',
        );
    }

    /** Retry pushing one group to Melipayamak. */
    public function sync(Request $request, ContactGroup $group, PhonebookSync $sync): RedirectResponse
    {
        $this->ensureEnabled();
        abort_unless($group->user_id === $request->user()->id, 403);

        $sync->pushGroup($group);

        return redirect()->route('dashboard.contacts.groups')
            ->with($group->fresh()->sync_status === 'synced' ? 'status' : 'warning', $this->flash($group->fresh(), 'همگام‌سازی گروه انجام شد.'));
    }

    /**
     * Pull just the group list from the customer's Melipayamak panel (one call,
     * synchronous). Contacts are pulled afterwards, per group, from the group
     * row ({@see pullContacts()}).
     */
    public function importGroups(Request $request, PhonebookSync $sync): RedirectResponse
    {
        $this->ensureEnabled();

        $user = $request->user();

        if (! $user->hasSmsPanel()) {
            return redirect()->route('dashboard.contacts.groups')
                ->with('error', 'پنل پیامک شما هنوز فعال نشده است؛ امکان دریافت از ملی‌پیامک وجود ندارد.');
        }

        try {
            $count = $sync->importGroups($user);

            return redirect()->route('dashboard.contacts.groups')->with(
                'status',
                sprintf('%d گروه از ملی‌پیامک دریافت شد. برای هر گروه، دکمهٔ «دریافت مخاطبین» را بزنید.', $count),
            );
        } catch (SmsPanelNotConfiguredException $e) {
            return redirect()->route('dashboard.contacts.groups')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[phonebook] group import failed', ['user' => $user->id, 'error' => $e->getMessage()]);

            return redirect()->route('dashboard.contacts.groups')
                ->with('error', 'دریافت گروه‌ها ناموفق بود: '.$e->getMessage());
        }
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'show_to_child' => ['boolean'],
        ], [], [
            'name' => 'نام گروه',
            'description' => 'توضیحات',
        ]) + ['show_to_child' => $request->boolean('show_to_child')];
    }

    private function flash(ContactGroup $group, string $ok): string
    {
        return $group->sync_status === 'error'
            ? $ok.' اما همگام‌سازی با ملی‌پیامک ناموفق بود: '.$group->sync_error
            : $ok;
    }

    private function ensureEnabled(): void
    {
        abort_unless((bool) Setting::get('phonebook_enabled', true), 404);
    }
}
