<?php

declare(strict_types=1);

namespace App\Support\AdminFiles;

use App\Models\Partner;
use App\Models\Sponsor;

class ReferencedFileFinder
{
    /**
     * @return array<int, array{label:string, model:string, id:int, field:string}>
     */
    public function references(string $path): array
    {
        $path = trim($path, '/');
        if (basename($path) === '' || basename($path) === '.' || ! str_contains($path, '/')) {
            return [];
        }

        if (str_starts_with($path, 'partners/')) {
            return array_merge(
                $this->partnerReferences($path, 'logo_light_filename', 'Partner:in Logo hell'),
                $this->partnerReferences($path, 'logo_dark_filename', 'Partner:in Logo dunkel'),
            );
        }

        if (str_starts_with($path, 'sponsors/')) {
            return $this->sponsorReferences($path);
        }

        return [];
    }

    /**
     * @return array<int, array{label:string, model:string, id:int, field:string}>
     */
    protected function partnerReferences(string $path, string $field, string $label): array
    {
        return Partner::query()
            ->whereIn($field, $this->referenceCandidates($path, 'partners'))
            ->orderBy('id')
            ->get(['id'])
            ->map(fn (Partner $partner): array => [
                'label' => $label.' #'.$partner->id,
                'model' => Partner::class,
                'id' => (int) $partner->id,
                'field' => $field,
            ])
            ->all();
    }

    /**
     * @return array<int, array{label:string, model:string, id:int, field:string}>
     */
    protected function sponsorReferences(string $path): array
    {
        return Sponsor::query()
            ->whereIn('logo_filename', $this->referenceCandidates($path, 'sponsors'))
            ->orderBy('id')
            ->get(['id'])
            ->map(fn (Sponsor $sponsor): array => [
                'label' => 'Sponsor:in Logo #'.$sponsor->id,
                'model' => Sponsor::class,
                'id' => (int) $sponsor->id,
                'field' => 'logo_filename',
            ])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function referenceCandidates(string $path, string $legacyDirectory): array
    {
        $prefix = $legacyDirectory.'/';

        if (! str_starts_with($path, $prefix)) {
            return [];
        }

        return [substr($path, strlen($prefix))];
    }
}
