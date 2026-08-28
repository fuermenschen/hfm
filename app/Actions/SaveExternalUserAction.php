<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ExternalUser;

class SaveExternalUserAction
{
    /**
     * @param  array{first_name:string, last_name:string, address:string, zip_code:string, city:string, country_of_residence:string, phone_number:string, email:string}  $data
     */
    public function __invoke(ExternalUser $externalUser, array $data): ExternalUser
    {
        $externalUser->fill([
            'first_name' => trim($data['first_name']),
            'last_name' => trim($data['last_name']),
            'address' => trim($data['address']),
            'zip_code' => trim($data['zip_code']),
            'city' => trim($data['city']),
            'country_of_residence' => $data['country_of_residence'],
            'phone_number' => trim($data['phone_number']),
            'email' => trim(mb_strtolower($data['email'])),
        ])->save();

        return $externalUser;
    }
}
