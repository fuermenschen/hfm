<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Sponsor;

class SaveSponsorAction
{
    /**
     * @param  array{name:string, description:string, logo_filename:string, url:string}  $data
     */
    public function __invoke(?Sponsor $sponsor, array $data): Sponsor
    {
        $sponsor ??= new Sponsor;

        $sponsor->fill([
            'name' => trim($data['name']),
            'description' => trim($data['description']),
            'logo_filename' => trim($data['logo_filename']),
            'url' => trim($data['url']),
        ])->save();

        return $sponsor;
    }
}
