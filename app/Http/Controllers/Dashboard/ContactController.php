<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Setting;
use App\Support\PhonebookSync;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Customer phonebook — contacts CRUD (docs/starter.md §17). Every mutation is
 * mirrored to the customer's own Melipayamak panel by {@see PhonebookSync},
 * best-effort: the local write always stands, a sync failure is shown as a
 * warning and recorded on the row. Lives behind auth + the "approved" gate.
 */
class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureEnabled();

        $user = $request->user();
        $keyword = trim((string) $request->query('keyword', ''));
        $groupId = $request->integer('group') ?: null;

        $contacts = $user->contacts()
            ->with('groups')
            ->when($keyword !== '', function ($query) use ($keyword) {
                $like = '%'.$keyword.'%';
                $query->where(fn ($q) => $q
                    ->where('mobile', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('company', 'like', $like));
            })
            ->when($groupId, fn ($query) => $query->whereHas('groups', fn ($q) => $q->whereKey($groupId)))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.contacts.index', [
            'contacts' => $contacts,
            'groups' => $user->contactGroups()->ordered()->withCount('contacts')->get(),
            'keyword' => $keyword,
            'activeGroup' => $groupId,
            'hasPanel' => $user->hasSmsPanel(),
        ]);
    }

    public function store(Request $request, PhonebookSync $sync): RedirectResponse
    {
        $this->ensureEnabled();

        $user = $request->user();
        $data = $this->validated($request, $user->id);

        $contact = $user->contacts()->create($this->attributes($data));
        $contact->groups()->sync($data['groups'] ?? []);

        $sync->pushContact($contact->load('groups'));

        return redirect()->route('dashboard.contacts', $request->only('keyword', 'group'))
            ->with($contact->fresh()->sync_status === 'error' ? 'warning' : 'status', $this->flash($contact->fresh(), 'مخاطب افزوده شد.'));
    }

    public function edit(Request $request, Contact $contact): View
    {
        $this->ensureEnabled();
        abort_unless($contact->user_id === $request->user()->id, 403);

        return view('dashboard.contacts.edit', [
            'contact' => $contact->load('groups'),
            'groups' => $request->user()->contactGroups()->ordered()->get(),
            'hasPanel' => $request->user()->hasSmsPanel(),
        ]);
    }

    public function update(Request $request, Contact $contact, PhonebookSync $sync): RedirectResponse
    {
        $this->ensureEnabled();
        abort_unless($contact->user_id === $request->user()->id, 403);

        $data = $this->validated($request, $request->user()->id, $contact->id);

        $contact->update($this->attributes($data));
        $contact->groups()->sync($data['groups'] ?? []);

        $sync->pushContact($contact->load('groups'));

        return redirect()->route('dashboard.contacts')
            ->with($contact->fresh()->sync_status === 'error' ? 'warning' : 'status', $this->flash($contact->fresh(), 'مخاطب ویرایش شد.'));
    }

    public function destroy(Request $request, Contact $contact, PhonebookSync $sync): RedirectResponse
    {
        $this->ensureEnabled();
        abort_unless($contact->user_id === $request->user()->id, 403);

        $sync->deleteContactRemote($contact);
        $contact->delete();

        return redirect()->route('dashboard.contacts')->with('status', 'مخاطب حذف شد.');
    }

    /** Progressive-enhancement check used by the add form. */
    public function checkMobile(Request $request): JsonResponse
    {
        $this->ensureEnabled();

        $mobile = normalize_mobile((string) $request->query('mobile', ''));
        $exists = $request->user()->contacts()->where('mobile', $mobile)->exists();

        return response()->json(['exists' => $exists]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, int $userId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'mobile' => [
                'required', 'string', 'regex:/^(0|\+?98)?9\d{9}$/',
                function ($attribute, $value, $fail) use ($userId, $ignoreId) {
                    $exists = Contact::query()
                        ->where('user_id', $userId)
                        ->where('mobile', normalize_mobile($value))
                        ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                        ->exists();

                    if ($exists) {
                        $fail('این شماره قبلاً در دفترچه تلفن شما ثبت شده است.');
                    }
                },
            ],
            'email' => ['nullable', 'email', 'max:150'],
            'company' => ['nullable', 'string', 'max:150'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', Rule::in(array_keys(Contact::GENDERS))],
            'birth_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'groups' => ['array'],
            'groups.*' => [Rule::exists('contact_groups', 'id')->where('user_id', $userId)],
        ], [], [
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'mobile' => 'شمارهٔ موبایل',
            'email' => 'ایمیل',
            'company' => 'شرکت',
            'nickname' => 'نام مستعار',
            'gender' => 'جنسیت',
            'birth_date' => 'تاریخ تولد',
            'description' => 'توضیحات',
            'groups' => 'گروه‌ها',
        ]);
    }

    /** @param  array<string, mixed>  $data */
    private function attributes(array $data): array
    {
        return [
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'mobile' => normalize_mobile($data['mobile']),
            'email' => $data['email'] ?? null,
            'company' => $data['company'] ?? null,
            'nickname' => $data['nickname'] ?? null,
            'gender' => $data['gender'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'description' => $data['description'] ?? null,
        ];
    }

    private function flash(Contact $contact, string $ok): string
    {
        return $contact->sync_status === 'error'
            ? $ok.' اما همگام‌سازی با ملی‌پیامک ناموفق بود: '.$contact->sync_error
            : $ok;
    }

    private function ensureEnabled(): void
    {
        abort_unless((bool) Setting::get('phonebook_enabled', true), 404);
    }
}
