<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FileUpload extends Component
{
    public function __construct(
        public string $id = 'file-upload',
        public string $label = 'Upload file',
        public string $name = 'file',
        public string $accept = 'image/png,image/jpeg,image/webp',
        public string $helpText = 'PNG, JPG, or WEBP',
        public int $maxSize = 2,
        public int $imageMaxSize = 5,
        public int $videoMaxSize = 100,
        public bool $multiple = false,
        public string $mode = 'deferred',
        public ?string $uploadUrl = null,
        public ?string $deleteUrl = null,
        public ?string $value = null,
        public ?string $previewUrl = null,
        public ?string $previewName = null,
        public ?string $hiddenName = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.file-upload');
    }
}
