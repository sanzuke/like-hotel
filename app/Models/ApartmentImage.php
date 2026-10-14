<?php

namespace App\Models;

use App\Support\ImageUploader;

class ApartmentImage extends BaseModel
{
    public function url(): ?string
    {
        return ImageUploader::url(Apartment::FOLDER, $this->image);
    }
}
