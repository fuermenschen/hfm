<?php

declare(strict_types=1);

namespace App\View\Components\Admin;

use App\Support\AdminFiles\AdminFileStorage;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Component;

class FileSelect extends Component
{
    /**
     * @var array<int, string>
     */
    public array $extensions;

    public function __construct(
        public string $directory = '',
        string|array $extensions = [],
        public bool $recursive = false,
        public ?string $label = null,
        public ?string $help = null,
        public ?string $placeholder = 'Datei auswählen',
        public ?string $selected = null,
        public bool $preview = true,
    ) {
        $this->extensions = $this->normalizeExtensions($extensions);
    }

    /**
     * @return array<int, array{path:string, name:string, directory:string, url:string, size:int, last_modified:int, extension:string}>
     */
    public function files(): array
    {
        return resolve(AdminFileStorage::class)->files(
            directory: $this->directory,
            recursive: $this->recursive,
            extensions: $this->extensions,
        );
    }

    public function selectedUrl(): ?string
    {
        if ($this->selected === null || $this->selected === '') {
            return null;
        }

        try {
            $path = resolve(AdminFileStorage::class)->normalizePath($this->storagePath($this->selected));
        } catch (\InvalidArgumentException) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function valueFor(string $path): string
    {
        $directory = trim($this->directory, '/');

        if ($directory === '') {
            return $path;
        }

        return str($path)->after($directory.'/')->toString();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin.file-select');
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeExtensions(string|array $extensions): array
    {
        if (is_string($extensions)) {
            $extensions = explode(',', $extensions);
        }

        return collect($extensions)
            ->map(fn (string $extension): string => strtolower(ltrim(trim($extension), '.')))
            ->filter()
            ->values()
            ->all();
    }

    protected function storagePath(string $value): string
    {
        $directory = trim($this->directory, '/');
        $value = ltrim($value, '/');

        return $directory === '' ? $value : $directory.'/'.$value;
    }
}
