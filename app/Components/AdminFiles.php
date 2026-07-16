<?php

declare(strict_types=1);

namespace App\Components;

use App\Support\AdminFiles\AdminFileStorage;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
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
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,png,jpg,jpeg,webp,svg,txt,csv,xlsx', 'extensions:pdf,png,jpg,jpeg,webp,svg,txt,csv,xlsx'],
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
        $this->ensureAuthenticated();

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
        $this->ensureAuthenticated();

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
        $this->ensureAuthenticated();

        $files = $this->files();

        try {
            $this->pendingDeletePath = $this->deletablePath($path);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            throw ValidationException::withMessages([
                'pendingDeletePath' => $invalidArgumentException->getMessage(),
            ]);
        }

        $this->pendingDeleteReferences = $files->references($this->pendingDeletePath);

        Flux::modal('delete-admin-file')->show();
    }

    public function deleteFile(): void
    {
        $this->ensureAuthenticated();

        if ($this->pendingDeletePath === null) {
            return;
        }

        try {
            $path = $this->deletablePath($this->pendingDeletePath);
            $this->files()->delete($path);
        } catch (\InvalidArgumentException|\RuntimeException $runtimeException) {
            $this->pendingDeleteReferences = [];

            throw ValidationException::withMessages([
                'pendingDeletePath' => $runtimeException->getMessage(),
            ]);
        }

        Flux::modal('delete-admin-file')->close();
        Flux::toast(heading: 'Datei gelöscht', text: $path, variant: 'success');

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

    protected function ensureAuthenticated(): void
    {
        abort_unless(Auth::check(), 403);
    }

    protected function deletablePath(string $path): string
    {
        $path = $this->files()->normalizePath($path);
        $directory = dirname($path) === '.' ? '' : dirname($path);
        $availablePaths = collect($this->files()->files($directory))->pluck('path');

        throw_unless($availablePaths->contains($path), \InvalidArgumentException::class, 'Datei ist nicht löschbar.');

        return $path;
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
