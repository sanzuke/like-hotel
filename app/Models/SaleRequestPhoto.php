<?php

namespace App\Models;

use App\Support\ImageUploader;

class SaleRequestPhoto extends BaseModel
{
    public const FOLDER = 'sale_requests';

    public function url(): ?string
    {
        return ImageUploader::url(self::FOLDER, $this->image);
    }
}
