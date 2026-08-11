<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AthleteRegistration;

class AthleteShareTextService
{
    /**
     * @return array<string, array{hochdeutsch:array{title:string,text:string},schweizerdeutsch:array{title:string,text:string}}>
     */
    public function templates(AthleteRegistration $registration): array
    {
        $event = $registration->donationEvent;
        $organization = $registration->partner->name ?? null;
        $donationLink = route('become-donor', ['sportlerin' => $registration->externalUser->public_id_string]);
        $base = $organization === null
            ? '100 % deiner Spende geht an die Benefizpartner des Anlasses.'
            : sprintf('100 %% deiner Spende geht an "%s".', $organization);
        $dialectBase = $organization === null
            ? '100 % vo dinere Spend gaht ad Benefizpartner vom Alass.'
            : sprintf('100 %% vo dinere Spend gaht a "%s".', $organization);

        return [
            'kurz' => [
                'hochdeutsch' => [
                    'title' => 'Kurz & direkt',
                    'text' => "Ich mache beim Spendenanlass \"{$event->title}\" mit und sammle Spenden.\n\nUnterstützt du mich mit einer Spende? Du entscheidest selbst, wie viel du pro Runde spendest. {$base}\n\nSpenden: {$donationLink}",
                ],
                'schweizerdeutsch' => [
                    'title' => 'Kurz & direkt',
                    'text' => "Ich mach bim Spendealass \"{$event->title}\" mit und sammle Spende.\n\nSpendisch für mich? Du chasch selber entscheide, wieviel du pro Rundi wetsch geh. {$dialectBase}\n\nSpände: {$donationLink}",
                ],
            ],
            'lang' => [
                'hochdeutsch' => [
                    'title' => 'Etwas ausführlicher',
                    'text' => "Ich mache beim Spendenanlass \"{$event->title}\" mit und sammle mit jeder absolvierten Runde Spenden.\n\nDu kannst mich dabei unterstützen und selbst bestimmen, wie viel du pro Runde spenden möchtest. Am Ende zählt jede Runde – und jeder Beitrag.\n\n{$base}\n\nWenn du mich unterstützen möchtest, findest du hier alle Infos:\n{$donationLink}",
                ],
                'schweizerdeutsch' => [
                    'title' => 'Etwas ausführlicher',
                    'text' => "Ich mache bim Spendealass \"{$event->title}\" mit und sammle mit jedere Rundi Spende.\n\nDu chasch mich unterstütze und selber bestimme, wie viel du pro Rundi wetsch geh. Am Schluss zellt jedi Rundi – und jede Biitrag.\n\n{$dialectBase}\n\nWenn mi wetsch unterstütze, findsch ali Infos da:\n{$donationLink}",
                ],
            ],

            'emotional' => [
                'hochdeutsch' => [
                    'title' => 'Für eine gute Sache',
                    'text' => "Für mich ist eine Runde beim Spendenanlass \"{$event->title}\" nur eine kleine sportliche Herausforderung. Nicht zu vergleichen mit den riesigen Herausforderungen, die andere Menschen Tag für Tag erleben.\n\nDarum mache ich mit und sammle mit jeder Runde Spenden. Denn die Organisationen, die hier unterstützt werden, machen wirklich einen Unterschied. Es wäre sehr schön von dir, wenn auch du einen Beitrag gibst. Du bestimmst selbst, wie viel dir eine meiner Runden wert ist.\n\n{$base}\n\nWenn du mich und damit diese Arbeit unterstützen möchtest:\n{$donationLink}",
                ],
                'schweizerdeutsch' => [
                    'title' => 'Für eine gute Sache',
                    'text' => "Für mich isch e Rundi am Spendealass \"{$event->title}\" nur e chliini sportlichi Useforderig. Ganz im Gegesatz zu de riesegrosse Useforderige, wo vil Mensche suscht täglich erlebed.\n\nDrum mach ich da mit und sammle mit jedere Rundi Spende. Denn die Organisatione, wo da unterstützt werded, mached würkli en Unterschied. Es wer mega vo dir, wenn du au öppis magsch Spende. Du bestimmsch selber, was dir ei Rundi vo mir Wert isch.\n\n{$dialectBase}\n\nWenn mi wetsch unterstütze:\n{$donationLink}",
                ],
            ],

        ];
    }
}
