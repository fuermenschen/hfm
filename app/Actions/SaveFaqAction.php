<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Faq;

class SaveFaqAction
{
    /**
     * @param  array{title: string, content_md: string}  $data
     */
    public function __invoke(?Faq $faq, array $data): Faq
    {
        $faq ??= new Faq;

        $faq->fill([
            'title' => trim($data['title']),
            'content_md' => trim($data['content_md']),
        ])->save();

        return $faq;
    }
}
