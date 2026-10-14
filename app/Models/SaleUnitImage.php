<?php

namespace App\Models;

use App\Support\ImageUploader;

class SaleUnitImage extends BaseModel
{
    public function url(): ?string
    {
        return ImageUploader::url(SaleUnit::FOLDER, $this->image);
    }
}
