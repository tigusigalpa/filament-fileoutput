<?php

namespace Tigusigalpa\FileOutput;

use Closure;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Storage;

class FileOutput extends Field
{
    protected string $view = 'filament-fileoutput::file-output';

    protected ?string $disk = null;

    protected ?string $fieldName = null;

    protected string|Closure|null $pathValue = null;

    protected ?Closure $deleteCallback = null;

    protected bool $showDeleteButton = true;

    protected bool $isImage = false;

    protected ?string $fileUrl = null;

    protected ?string $filePath = null;

    protected ?array $files = null;

    public function disk(?string $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function field(string $fieldName): static
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function path(string|Closure $path): static
    {
        $this->pathValue = $path;

        return $this;
    }

    public function onDelete(?Closure $callback): static
    {
        $this->deleteCallback = $callback;

        return $this;
    }

    public function showDeleteButton(bool $condition = true): static
    {
        $this->showDeleteButton = $condition;

        return $this;
    }

    public function hideDeleteButton(bool $condition = true): static
    {
        $this->showDeleteButton = !$condition;

        return $this;
    }

    public function getDisk(): ?string
    {
        return $this->disk;
    }

    public function getFieldName(): ?string
    {
        return $this->fieldName;
    }

    public function getDeleteCallback(): ?Closure
    {
        return $this->deleteCallback;
    }

    public function getFileUrl(): ?string
    {
        if ($this->fileUrl !== null) {
            return $this->fileUrl;
        }

        $path = $this->getFilePath();

        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $this->fileUrl = $path;
            return $this->fileUrl;
        }

        if ($this->disk) {
            $storage = Storage::disk($this->disk);
            
            if (!$storage->exists($path)) {
                return null;
            }

            if (method_exists($storage, 'temporaryUrl')) {
                try {
                    $this->fileUrl = $storage->temporaryUrl($path, now()->addMinutes(5));
                } catch (\Exception $e) {
                    $this->fileUrl = route('filament-fileoutput.download', [
                        'disk' => $this->disk,
                        'path' => base64_encode($path),
                    ]);
                }
            } else {
                $this->fileUrl = route('filament-fileoutput.download', [
                    'disk' => $this->disk,
                    'path' => base64_encode($path),
                ]);
            }
        } else {
            $this->fileUrl = Storage::url($path);
        }

        return $this->fileUrl;
    }

    public function getFilePath(): string|array|null
    {
        if ($this->filePath !== null) {
            return $this->filePath;
        }

        if ($this->pathValue !== null) {
            $this->filePath = $this->evaluate($this->pathValue);
            return $this->filePath;
        }

        if (!$this->fieldName) {
            return null;
        }

        $record = $this->getRecord();
        
        if (!$record) {
            $state = $this->getState();
            if (is_string($state) || is_array($state)) {
                $this->filePath = $state;
                return $this->filePath;
            }
            return null;
        }

        $path = data_get($record, $this->fieldName);

        if (!$path) {
            return null;
        }

        $this->filePath = $path;

        return $this->filePath;
    }

    public function isImage(): bool
    {
        if ($this->isImage) {
            return true;
        }

        $path = $this->getFilePath();

        if (!$path) {
            return false;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'ico'];

        $this->isImage = in_array($extension, $imageExtensions);

        return $this->isImage;
    }

    public function hasFile(): bool
    {
        $path = $this->getFilePath();
        
        if (is_array($path)) {
            return !empty($path);
        }
        
        return $path !== null;
    }

    public function isMultiple(): bool
    {
        return is_array($this->getFilePath());
    }

    public function getFiles(): array
    {
        if ($this->files !== null) {
            return $this->files;
        }

        $path = $this->getFilePath();

        if (!$path) {
            $this->files = [];
            return $this->files;
        }

        if (is_string($path)) {
            $this->files = [$path];
        } else {
            $this->files = $path;
        }

        return $this->files;
    }

    public function getFileUrlForPath(string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if ($this->disk) {
            $storage = Storage::disk($this->disk);
            
            if (!$storage->exists($path)) {
                return null;
            }

            if (method_exists($storage, 'temporaryUrl')) {
                try {
                    return $storage->temporaryUrl($path, now()->addMinutes(5));
                } catch (\Exception $e) {
                    return route('filament-fileoutput.download', [
                        'disk' => $this->disk,
                        'path' => base64_encode($path),
                    ]);
                }
            } else {
                return route('filament-fileoutput.download', [
                    'disk' => $this->disk,
                    'path' => base64_encode($path),
                ]);
            }
        } else {
            return Storage::url($path);
        }
    }

    public function isImagePath(string $path): bool
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'ico'];
        return in_array($extension, $imageExtensions);
    }

    public function getDeleteAction(?string $filePathToDelete = null): ?Action
    {
        if (!$this->deleteCallback || !$this->showDeleteButton) {
            return null;
        }

        return Action::make($filePathToDelete ? 'deleteFile_' . md5($filePathToDelete) : 'deleteFile')
            ->label(__('Delete'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function () use ($filePathToDelete) {
                $filePath = $filePathToDelete ?? $this->getFilePath();
                $disk = $this->getDisk();
                
                call_user_func($this->deleteCallback, $filePath, $disk);
                
                if ($this->fieldName) {
                    $livewire = $this->getLivewire();
                    $currentValue = data_get($livewire, $this->fieldName);
                    
                    if ($filePathToDelete && is_array($currentValue)) {
                        $newValue = array_values(array_filter($currentValue, fn($path) => $path !== $filePathToDelete));
                        data_set($livewire, $this->fieldName, empty($newValue) ? null : $newValue);
                    } else {
                        data_set($livewire, $this->fieldName, null);
                    }
                    
                    if (method_exists($livewire, 'refreshFormData')) {
                        $livewire->refreshFormData([$this->fieldName]);
                    }
                }
            });
    }
}
