<?php

namespace App\Services\Sms\Phonebook;

use App\Services\Sms\MelipayamakProvider;
use App\Services\Sms\Phonebook\Concerns\ParsesAsmxResponses;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Melipayamak phonebook driver (docs/starter.md §13 / §17). Username/password
 * mode only — the classic Contacts.asmx web service on api.payamak-panel.com,
 * the same host {@see MelipayamakProvider} uses for sending.
 *
 * Notes from the vendor docs that shape this class:
 *  - AddGroup / AddContact reply with a bare `1` (ok) or `0` (fail) — no new id,
 *    so the caller re-reads {@see groups()} / {@see contacts()} to learn it.
 *  - ChangeContact2 has no group field; it does take `contactStatus`
 *    (0 active / 1 inactive / -1 unchanged) — our "delete" sets it inactive.
 *  - SendSmsToContact lives on newbulks.asmx and is a GET.
 */
class MelipayamakPhonebookClient implements PhonebookClientInterface
{
    use ParsesAsmxResponses;

    /** @param  array<string, string|null>  $config  username / password / sender */
    public function __construct(private readonly array $config) {}

    public function groups(): array
    {
        $rows = $this->xmlRecords(
            $this->asmxPost('Contacts.asmx/GetGroups', []),
            'GroupsList',
            ['GroupID', 'ParentId', 'GroupName', 'GroupDescription', 'ContactCount', 'ShowToChild'],
        );

        return array_values(array_filter(array_map(fn (array $r) => [
            'remote_id' => (int) $r['GroupID'],
            'name' => $r['GroupName'],
            'description' => $r['GroupDescription'] !== '' ? $r['GroupDescription'] : null,
            'parent_id' => $r['ParentId'] !== '' ? (int) $r['ParentId'] : null,
            'contact_count' => (int) $r['ContactCount'],
            'show_to_child' => in_array(strtolower($r['ShowToChild']), ['true', '1'], true),
        ], $rows), fn (array $g) => $g['remote_id'] > 0));
    }

    public function contacts(?int $groupId = null, ?string $keyword = null, int $from = 0, int $count = 200): array
    {
        $body = $this->asmxPost('Contacts.asmx/GetContacts', [
            'GroupId' => $groupId ?? 0,
            'Keyword' => $keyword ?? '',
            'From' => max(0, $from),
            'Count' => max(1, $count),
        ]);

        // The repeating record element is <ContactsGridList> (docs:
        // https://www.melipayamak.com/api/getcontacts/ — the "خروجی" sample).
        $rows = $this->xmlRecords($body, 'ContactsGridList', [
            'ContactID', 'FirstName', 'LastName', 'NickName', 'Corporation',
            'MobileNumbers', 'Email', 'Gender', 'BirthDate', 'Descriptions', 'Groups',
        ]);

        return array_values(array_filter(array_map(fn (array $r) => [
            'remote_id' => (int) $r['ContactID'],
            'first_name' => $r['FirstName'] !== '' ? $r['FirstName'] : null,
            'last_name' => $r['LastName'] !== '' ? $r['LastName'] : null,
            'mobile' => normalize_mobile($r['MobileNumbers']),
            'email' => $r['Email'] !== '' ? $r['Email'] : null,
            'company' => $r['Corporation'] !== '' ? $r['Corporation'] : null,
            'nickname' => $r['NickName'] !== '' ? $r['NickName'] : null,
            'gender' => $this->genderFromRemote($r['Gender']),
            'birth_date' => $this->dateFromRemote($r['BirthDate']),
            'description' => $r['Descriptions'] !== '' ? $r['Descriptions'] : null,
            'group_ids' => $this->digitsList($r['Groups']),
        ], $rows), fn (array $c) => $c['remote_id'] > 0 && $c['mobile'] !== ''));
    }

    public function createGroup(string $name, ?string $description, bool $showToChild): bool
    {
        return $this->ok($this->asmxPost('Contacts.asmx/AddGroup', [
            'GroupName' => $name,
            'Descriptions' => $description ?? '',
            'Showtochilds' => $showToChild ? 'true' : 'false',
        ]));
    }

    public function createContact(array $data): bool
    {
        return $this->ok($this->asmxPost('Contacts.asmx/AddContact', array_merge([
            'groupIds' => '',
            'firstname' => '',
            'lastname' => '',
            'nickname' => '',
            'corporation' => '',
            'mobilenumber' => '',
            'phone' => '',
            'fax' => '',
            'birthdate' => '',
            'email' => '',
            'gender' => '',
            'descriptions' => '',
        ], $data)));
    }

    public function updateContact(int $remoteId, array $data): bool
    {
        return $this->ok($this->asmxPost('Contacts.asmx/ChangeContact2', array_merge([
            'contactId' => $remoteId,
            'mobilenumber' => '',
            'firstname' => '',
            'lastname' => '',
            'nickname' => '',
            'corporation' => '',
            'phone' => '',
            'fax' => '',
            'email' => '',
            'gender' => '',
            'birthdate' => '',
            'descriptions' => '',
            'contactStatus' => -1,
        ], $data, ['contactId' => $remoteId])));
    }

    public function deactivateContact(int $remoteId): bool
    {
        return $this->updateContact($remoteId, ['contactStatus' => 1]);
    }

    public function checkMobile(string $mobile): int
    {
        $value = $this->xmlScalar($this->asmxPost('Contacts.asmx/CheckMobileExistInContact', [
            'mobileNumber' => normalize_mobile($mobile),
        ]));

        return is_numeric($value) ? (int) $value : -1;
    }

    public function sendToGroups(array $remoteGroupIds, string $message, ?string $from = null, ?string $title = null, ?string $dateToSend = null): string
    {
        $ids = array_values(array_filter(array_map('intval', $remoteGroupIds), fn ($id) => $id > 0));

        if ($ids === []) {
            throw new RuntimeException('هیچ گروه معتبری برای ارسال انتخاب نشده است.');
        }

        $value = $this->xmlScalar($this->asmxGet('newbulks.asmx/SendSmsToContact', [
            'title' => $title ?: 'ارسال گروهی دفترچه تلفن',
            'message' => $message,
            'from' => $from ?: ($this->config['sender'] ?? ''),
            'groupId' => implode(',', array_slice($ids, 0, 5)),
            'dateToSend' => $dateToSend ?? '',
        ]));

        // A real bulkId is a long run of digits; small / zero / negative is a status code.
        if (preg_match('/^\d{4,}$/', $value)) {
            return $value;
        }

        throw new RuntimeException($this->sendError($value));
    }

    private function ok(string $body): bool
    {
        return $this->xmlScalar($body) === '1';
    }

    private function genderFromRemote(string $value): ?string
    {
        return match (trim($value)) {
            '1' => 'female',
            '2' => 'male',
            default => null,
        };
    }

    private function dateFromRemote(string $value): ?string
    {
        $value = trim($value);

        if ($value === '' || str_starts_with($value, '0001-01-01') || str_starts_with($value, '1/1/0001')) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return array<int, int> */
    private function digitsList(string $value): array
    {
        preg_match_all('/\d+/', $value, $m);

        return array_values(array_unique(array_map('intval', $m[0])));
    }

    private function sendError(string $code): string
    {
        return match ($code) {
            '-1' => 'ارسال تکراری در بازهٔ ۳۰ ثانیه.',
            '0' => 'نام کاربری یا رمز عبور پنل پیامک نادرست است.',
            '1' => 'تعداد شماره‌های گروه‌های انتخاب‌شده کمتر از ۱۰ است.',
            '2' => 'اعتبار پنل پیامک کافی نیست.',
            '3' => 'ارسال خارج از بازهٔ مجاز (۸ صبح تا ۱۰ شب).',
            '4' => 'تاریخ زمان‌بندی نامعتبر است.',
            '5' => 'شمارهٔ فرستنده معتبر نیست.',
            '6' => 'هیچ گروهی انتخاب نشده است.',
            '7' => 'حداکثر ۵ گروه در هر ارسال مجاز است.',
            default => 'ارسال گروهی ناموفق بود (کد: '.$code.').',
        };
    }
}
