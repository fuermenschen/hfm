<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Partner;

class SavePartnerAction
{
    /**
     * @param  array{name:string, logo_light_filename:string, logo_dark_filename:string, beneficiary_blurb:string, url:string}  $data
     */
    public function __invoke(?Partner $partner, array $data): Partner
    {
        $partner ??= new Partner;

        $partner->fill([
            'name' => trim($data['name']),
            'logo_light_filename' => trim($data['logo_light_filename']),
            'logo_dark_filename' => trim($data['logo_dark_filename']),
            'beneficiary_blurb' => trim($data['beneficiary_blurb']),
            'url' => trim($data['url']),
        ])->save();

        return $partner;
    }
}
