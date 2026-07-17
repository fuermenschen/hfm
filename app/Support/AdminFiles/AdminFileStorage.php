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
        $path = $file->storeAs($directory, $filename, 'public');

        throw_if(! is_string($path) || $path === '', \RuntimeException::class, 'Datei konnte nicht gespeichert werden.');

        return $path;
    }

    public function createDirectory(string $directory, string $name): string
    {
        $directory = $this->normalizeDirectory($directory);
        $name = $this->normalizeDirectory($name);

        throw_if($name === '', \InvalidArgumentException::class, 'Ordnername darf nicht leer sein.');

        $path = $this->joinPath($directory, $name);

        throw_unless(Storage::disk('public')->makeDirectory($path), \RuntimeException::class, 'Ordner konnte nicht erstellt werden.');

        return $path;
    }

    public function deleteEmptyDirectory(string $directory): void
    {
        $directory = $this->emptyDirectory($directory);

        throw_unless($this->removeEmptyDirectory($directory), \RuntimeException::class, 'Ordner konnte nicht gelöscht werden.');
    }

    public function renameEmptyDirectory(string $directory, string $name): string
    {
        $directory = $this->emptyDirectory($directory);
        $parent = dirname($directory) === '.' ? '' : dirname($directory);
        $target = $this->joinPath($parent, $this->safeDirectoryName($name));
        $disk = Storage::disk('public');

        throw_if($target === $directory, \InvalidArgumentException::class, 'Der neue Ordnername ist unverändert.');
        throw_if($disk->exists($target) || $disk->directoryExists($target), \InvalidArgumentException::class, 'Eine Datei oder ein Ordner mit diesem Namen existiert bereits.');
        throw_unless($disk->makeDirectory($target), \RuntimeException::class, 'Ordner konnte nicht umbenannt werden.');

        if (! $this->removeEmptyDirectory($directory)) {
            $this->removeEmptyDirectory($target);

            throw new \RuntimeException('Ordner konnte nicht umbenannt werden.');
        }

        return $target;
    }

    public function renameFile(string $path, string $name): string
    {
        $path = $this->normalizePath($path);
        $disk = Storage::disk('public');

        throw_unless($disk->exists($path), \InvalidArgumentException::class, 'Datei wurde nicht gefunden.');
        throw_if($this->references($path) !== [], \RuntimeException::class, 'Datei wird noch verwendet und kann nicht umbenannt werden.');

        $directory = dirname($path) === '.' ? '' : dirname($path);
        $target = $this->joinPath($directory, $this->renamedFilename($path, $name));

        throw_if($target === $path, \InvalidArgumentException::class, 'Der neue Dateiname ist unverändert.');
        throw_if($disk->exists($target) || $disk->directoryExists($target), \InvalidArgumentException::class, 'Eine Datei oder ein Ordner mit diesem Namen existiert bereits.');
        throw_unless($disk->move($path, $target), \RuntimeException::class, 'Datei konnte nicht umbenannt werden.');

        return $target;
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

    protected function safeDirectoryName(string $name): string
    {
        $name = trim($name);

        throw_if(in_array($name, ['', '.', '..'], true) || str_contains($name, '/') || str_contains($name, '\\') || preg_match('/[[:cntrl:]]/u', $name) === 1, \InvalidArgumentException::class, 'Ungültiger Ordnername.');

        return $name;
    }

    protected function emptyDirectory(string $directory): string
    {
        $directory = $this->normalizeDirectory($directory);
        $disk = Storage::disk('public');

        throw_if($directory === '', \InvalidArgumentException::class, 'Der Hauptordner kann nicht geändert werden.');
        throw_unless($disk->directoryExists($directory), \InvalidArgumentException::class, 'Ordner wurde nicht gefunden.');
        throw_if($disk->allFiles($directory) !== [] || $disk->allDirectories($directory) !== [], \InvalidArgumentException::class, 'Nur leere Ordner können geändert werden.');

        return $directory;
    }

    /** Avoid recursive deletion if a file appears after the emptiness check. */
    protected function removeEmptyDirectory(string $directory): bool
    {
        return @rmdir(Storage::disk('public')->path($directory));
    }

    protected function renamedFilename(string $path, string $name): string
    {
        $name = trim($name);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $requestedExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        throw_if($name === '', \InvalidArgumentException::class, 'Dateiname darf nicht leer sein.');
        throw_if($requestedExtension !== '' && $requestedExtension !== $extension, \InvalidArgumentException::class, 'Die Dateiendung darf nicht geändert werden.');

        if ($requestedExtension !== '') {
            $name = pathinfo($name, PATHINFO_FILENAME);
        }

        return $this->safeFilename($extension === '' ? $name : $name.'.'.$extension);
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
        $trashPath = $this->trashPath($path);
        $stream = $publicDisk->readStream($path);

        throw_unless(is_resource($stream), \RuntimeException::class, 'Datei konnte nicht in den Papierkorb verschoben werden.');

        $localDisk = Storage::disk('local');

        try {
            $stored = $localDisk->put($trashPath, $stream);
        } finally {
            fclose($stream);
        }

        throw_if(! $stored, \RuntimeException::class, 'Datei konnte nicht in den Papierkorb verschoben werden.');

        throw_unless($publicDisk->delete($path), \RuntimeException::class, 'Datei konnte nicht gelöscht werden.');
    }

    protected function trashPath(string $path): string
    {
        $directory = dirname($path) === '.' ? '' : dirname($path);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $name = pathinfo($path, PATHINFO_FILENAME);
        $deletedAt = now()->format('Ymd-His');
        $suffix = Str::random(8);
        $filename = $extension === ''
            ? $name.'.deleted-'.$deletedAt.'-'.$suffix
            : $name.'.deleted-'.$deletedAt.'-'.$suffix.'.'.$extension;

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
