<?php

namespace App\Http\Requests\Farmer\Concerns;

trait ValidatesHealthAttachment
{
    /**
     * @return array<string, list<string>>
     */
    protected function healthAttachmentRules(): array
    {
        return [
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
