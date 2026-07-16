<?php

declare(strict_types=1);

namespace App\Support\AdminFiles;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminFileStorage
{
    public function __construct(protected ReferencedFileFinder $referencedFileFinder) {}

    public function store(UploadedFile $file, string $directory = ''): string
    {
        $directory = $this->normalizeDirectory($directory);
        $filename = $this->availableFilename($directory, $this->safeFilename($file->getClientOriginalName()));

        return $file->storeAs($directory, $filename, 'public');
    }

    public function createDirectory(string $directory, string $name): string
    {
        $directory = $this->normalizeDirectory($directory);
        $name = $this->normalizeDirectory($name);

        throw_if($name === '', \InvalidArgumentException::class, 'Folder name cannot be empty.');

        $path = $this->joinPath($directory, $name);

        Storage::disk('public')->makeDirectory($path);

        return $path;
    }

    /**
     * @param  array<int, string>  $extensions
     * @return array<int, array{path:string, name:string, directory:string, url:string, size:int, last_modified:int, extension:string}>
     */
    public function files(string $directory = '', bool $recursive = false, array $extensions = []): array
    {
        $directory = $this->normalizeDirectory($directory);
        $extensions = array_values(array_filter(array_map(
            static fn (string $extension): string => strtolower(ltrim($extension, '.')),
            $extensions,
        )));

        $paths = $recursive
            ? Storage::disk('public')->allFiles($directory)
            : Storage::disk('public')->files($directory);

        return collect($paths)
            ->reject(fn (string $path): bool => str_starts_with(basename($path), '.'))
            ->filter(fn (string $path): bool => $extensions === [] || in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), $extensions, true))
            ->sort()
            ->values()
            ->map(fn (string $path): array => $this->fileDetails($path))
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function directories(string $directory = ''): array
    {
        $directory = $this->normalizeDirectory($directory);

        return collect(Storage::disk('public')->directories($directory))
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label:string, model:string, id:int, field:string}>
     */
    public function references(string $path): array
    {
        return $this->referencedFileFinder->references($this->normalizePath($path));
    }

    public function delete(string $path): void
    {
        $path = $this->normalizePath($path);
        $references = $this->references($path);

        throw_if($references !== [], \RuntimeException::class, 'Datei wird noch verwendet und kann nicht gelöscht werden.');

        $this->moveToTrash($path);
    }

    public function normalizeDirectory(string $directory): string
    {
        if ($directory === '' || $directory === '/') {
            return '';
        }

        $segments = $this->safeSegments($directory);

        return implode('/', $segments);
    }

    public function normalizePath(string $path): string
    {
        $segments = $this->safeSegments($path);

        throw_if($segments === [], \InvalidArgumentException::class, 'File path cannot be empty.');

        return implode('/', $segments);
    }

    /**
     * @return array<int, string>
     */
    protected function safeSegments(string $path): array
    {
        $path = str_replace('\\', '/', trim($path));

        return collect(explode('/', trim($path, '/')))
            ->map(fn (string $segment): string => trim($segment))
            ->reject(fn (string $segment): bool => $segment === '')
            ->map(function (string $segment): string {
                throw_if($segment === '.' || $segment === '..' || preg_match('/[[:cntrl:]]/u', $segment) === 1, \InvalidArgumentException::class, 'Invalid file path.');

                return $segment;
            })
            ->values()
            ->all();
    }

    protected function safeFilename(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = Str::slug($name) ?: 'file';

        return $extension === '' ? $name : $name.'.'.$extension;
    }

    protected function availableFilename(string $directory, string $filename): string
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $candidate = $filename;
        $counter = 2;

        while (Storage::disk('public')->exists($this->joinPath($directory, $candidate))) {
            $candidate = $extension === ''
                ? $name.'-'.$counter
                : $name.'-'.$counter.'.'.$extension;
            $counter++;
        }

        return $candidate;
    }

    protected function joinPath(string $directory, string $filename): string
    {
        return $directory === '' ? $filename : $directory.'/'.$filename;
    }

    protected function moveToTrash(string $path): void
    {
        $publicDisk = Storage::disk('public');
        $localDisk = Storage::disk('local');
        $trashPath = $this->trashPath($path);
        $stream = $publicDisk->readStream($path);

        throw_if($stream === false, \RuntimeException::class, 'Datei konnte nicht in den Papierkorb verschoben werden.');

        try {
            $stored = $localDisk->put($trashPath, $stream);
        } finally {
            fclose($stream);
        }

        throw_if(! $stored, \RuntimeException::class, 'Datei konnte nicht in den Papierkorb verschoben werden.');

        $publicDisk->delete($path);
    }

    protected function trashPath(string $path): string
    {
        $directory = dirname($path) === '.' ? '' : dirname($path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $deletedAt = now()->format('Ymd-His');
        $filename = $extension === ''
            ? $name.'.deleted-'.$deletedAt
            : $name.'.deleted-'.$deletedAt.'.'.$extension;

        $trashDirectory = $this->joinPath('trash/admin-files', $directory);

        return $this->joinPath($trashDirectory, $filename);
    }

    /**
     * @return array{path:string, name:string, directory:string, url:string, size:int, last_modified:int, extension:string}
     */
    protected function fileDetails(string $path): array
    {
        $disk = Storage::disk('public');

        return [
            'path' => $path,
            'name' => basename($path),
            'directory' => dirname($path) === '.' ? '' : dirname($path),
            'url' => $disk->url($path),
            'size' => $disk->size($path),
            'last_modified' => $disk->lastModified($path),
            'extension' => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
        ];
    }
}
