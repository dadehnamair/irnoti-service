<?php

namespace App\Http\Controllers;

use App\Models\BusinessCard;
use App\Models\Domain;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Public digital-business-card page, resolved by the request's Host header
 * against the `domains` table so the same route works unchanged on
 * irnoti.com, 11v.ir, 7db.ir, and any future domain added from the admin
 * panel. Registered last in routes/web.php so it only catches paths nothing
 * else claimed.
 */
class PublicBusinessCardController extends Controller
{
    public function show(Request $request, string $code): View|Response
    {
        $domain = Domain::query()->where('host', $request->getHost())->active()->first();

        abort_unless($domain, 404);

        $card = BusinessCard::query()
            ->where('domain_id', $domain->id)
            ->where('code', $code)
            ->where('status', 'active')
            ->first();

        abort_unless($card, 404);

        if ($request->boolean('vcf')) {
            return $this->vcf($card);
        }

        $card->increment('views_count');

        return view('cards.show', ['card' => $card]);
    }

    /** Download the card as a .vcf contact file. */
    private function vcf(BusinessCard $card): Response
    {
        $name = $card->title ?: $card->code;

        $lines = [
            'BEGIN:VCARD',
            'VERSION:3.0',
            'FN:'.$name,
            $card->company ? 'ORG:'.$card->company : null,
            $card->position ? 'TITLE:'.$card->position : null,
            $card->phone ? 'TEL;TYPE=WORK,VOICE:'.$card->phone : null,
            $card->mobile ? 'TEL;TYPE=CELL:'.$card->mobile : null,
            $card->email ? 'EMAIL:'.$card->email : null,
            $card->website ? 'URL:'.$card->website : null,
            $card->address ? 'ADR;TYPE=WORK:;;'.$card->address : null,
            'END:VCARD',
        ];

        $vcf = implode("\r\n", array_filter($lines))."\r\n";

        return response($vcf, 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$card->code.'.vcf"',
        ]);
    }
}
