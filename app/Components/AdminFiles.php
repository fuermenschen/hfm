<?php

declare(strict_types=1);

namespace App\Components;

use App\Support\AdminFiles\AdminFileStorage;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AdminFiles extends Component
{
    use WithFileUploads;

    public string $directory = '';

    public ?TemporaryUploadedFile $file = null;

    public ?string $newFolderName = null;

    public ?string $pendingDeletePath = null;

    /**
     * @var array<int, array{label:string, model:string, id:int, field:string}>
     */
    public array $pendingDeleteReferences = [];

    public function render(): View
    {
        $files = $this->files();
        $directory = $this->safeDirectory();

        return view('components.admin-files', [
            'files' => $files->files($directory),
            'directories' => $files->directories($directory),
            'breadcrumbs' => $this->breadcrumbs($directory),
        ]);
    }

    public function rules(): array
    {
        return [
            'directory' => ['nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:1048576'],
        ];
    }

    public function updatedDirectory(): void
    {
        try {
            $this->directory = $this->files()->normalizeDirectory($this->directory);
            $this->resetErrorBag('directory');
        } catch (\InvalidArgumentException) {
            $this->addError('directory', 'Ungültiger Ordner.');
        }
    }

    public function updatedFile(): void
    {
        if ($this->file instanceof TemporaryUploadedFile) {
            $this->storeFile();
        }
    }

    public function storeFile(): void
    {
        if (! $this->file instanceof TemporaryUploadedFile) {
            return;
        }

        $this->validate();

        try {
            $path = $this->files()->store($this->file, $this->directory);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            throw ValidationException::withMessages([
                'directory' => $invalidArgumentException->getMessage(),
            ]);
        }

        $this->reset('file');

        Flux::modal('upload-admin-file')->close();
        Flux::toast(heading: 'Datei hochgeladen', text: $path, variant: 'success');
    }

    public function openDirectory(string $directory): void
    {
        $this->directory = $this->files()->normalizeDirectory($directory);
    }

    public function openParentDirectory(): void
    {
        $directory = $this->files()->normalizeDirectory($this->directory);

        $this->directory = dirname($directory) === '.' ? '' : dirname($directory);
    }

    public function createFolder(): void
    {
        try {
            $path = $this->files()->createDirectory($this->directory, (string) $this->newFolderName);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            throw ValidationException::withMessages([
                'newFolderName' => $invalidArgumentException->getMessage(),
            ]);
        }

        $this->newFolderName = null;

        Flux::modal('create-admin-folder')->close();
        Flux::toast(heading: 'Ordner erstellt', text: $path, variant: 'success');
    }

    public function confirmDelete(string $path): void
    {
        $files = $this->files();

        $this->pendingDeletePath = $files->normalizePath($path);
        $this->pendingDeleteReferences = $files->references($this->pendingDeletePath);

        Flux::modal('delete-admin-file')->show();
    }

    public function deleteFile(): void
    {
        if ($this->pendingDeletePath === null) {
            return;
        }

        try {
            $this->files()->delete($this->pendingDeletePath);
        } catch (\RuntimeException $runtimeException) {
            $this->pendingDeleteReferences = $this->files()->references($this->pendingDeletePath);

            throw ValidationException::withMessages([
                'pendingDeletePath' => $runtimeException->getMessage(),
            ]);
        }

        Flux::modal('delete-admin-file')->close();
        Flux::toast(heading: 'Datei gelöscht', text: $this->pendingDeletePath, variant: 'success');

        $this->pendingDeletePath = null;
        $this->pendingDeleteReferences = [];
    }

    public function cancelDelete(): void
    {
        $this->pendingDeletePath = null;
        $this->pendingDeleteReferences = [];

        Flux::modal('delete-admin-file')->close();
    }

    protected function files(): AdminFileStorage
    {
        return resolve(AdminFileStorage::class);
    }

    protected function safeDirectory(): string
    {
        try {
            return $this->files()->normalizeDirectory($this->directory);
        } catch (\InvalidArgumentException) {
            return '';
        }
    }

    /**
     * @return array<int, array{label:string, path:string, current:bool}>
     */
    protected function breadcrumbs(string $directory): array
    {
        $breadcrumbs = [[
            'label' => 'Dateien',
            'path' => '',
            'current' => $directory === '',
        ]];

        $path = '';
        foreach (explode('/', $directory) as $segment) {
            if ($segment === '') {
                continue;
            }

            $path = $path === '' ? $segment : $path.'/'.$segment;
            $breadcrumbs[] = [
                'label' => $segment,
                'path' => $path,
                'current' => $path === $directory,
            ];
        }

        return $breadcrumbs;
    }
}
