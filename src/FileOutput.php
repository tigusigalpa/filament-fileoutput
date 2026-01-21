<?php

namespace Tigusigalpa\FileOutput;

use Closure;
use Exception;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Storage;

class FileOutput extends Field
{
    protected string $view = 'filament-fileoutput::file-output';

    protected ?string $disk = null;

    protected ?string $fieldName = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerActions([
            fn (): Action => $this->makeDeleteAction(),
        ]);
    }

    protected string|Closure|null $pathValue = null;

    protected ?Closure $deleteCallback = null;

    protected bool $showDeleteButton = true;

    protected bool $isImage = false;

    protected ?string $fileUrl = null;

    protected string|array|null $filePath = null;

    protected ?array $files = null;

    protected ?array $fileLabels = null;

    protected string|array|Closure|null $description = null;

    protected string|Closure|null $deleteConfirmationTitle = null;

    protected string|Closure|null $deleteConfirmationDescription = null;

    protected string|Closure|null $deleteLabel = null;

    protected string|Closure|null $emptyState = null;

    public function field(string $fieldName): static
    {
        $this->fieldName = $fieldName;

        return $this;
    }

    public function path(string|array|Closure $path): static
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

    public function description(string|array|Closure $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function deleteConfirmationTitle(string|Closure $title): static
    {
        $this->deleteConfirmationTitle = $title;

        return $this;
    }

    public function deleteConfirmationDescription(string|Closure $description): static
    {
        $this->deleteConfirmationDescription = $description;

        return $this;
    }

    public function deleteLabel(string|Closure $label): static
    {
        $this->deleteLabel = $label;

        return $this;
    }

    public function emptyState(string|Closure $message): static
    {
        $this->emptyState = $message;

        return $this;
    }

    public function getEmptyState(): string
    {
        if ($this->emptyState !== null) {
            return $this->evaluate($this->emptyState);
        }

        return __('No file uploaded');
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
                } catch (Exception $e) {
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

    public function disk(?string $disk): static
    {
        $this->disk = $disk;

        return $this;
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
                } catch (Exception $e) {
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

    public function getDescription(?string $filePath = null): ?string
    {
        if ($this->description === null) {
            return null;
        }

        $description = $this->evaluate($this->description);

        if (is_string($description)) {
            return $description;
        }

        if (is_array($description) && $filePath !== null) {
            // Check if it's an associative array with path as key
            if (isset($description[$filePath])) {
                return $description[$filePath];
            }

            // Fallback to indexed array
            $files = $this->getFiles();
            $index = array_search($filePath, $files);

            if ($index !== false && isset($description[$index])) {
                return $description[$index];
            }
        }

        return null;
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
        } elseif ($this->isAssociativeArray($path)) {
            $this->fileLabels = $path;
            $this->files = array_keys($path);
        } else {
            $this->files = $path;
        }

        return $this->files;
    }

    public function getFileLabel(string $filePath): string
    {
        if ($this->fileLabels !== null && isset($this->fileLabels[$filePath])) {
            return $this->fileLabels[$filePath];
        }

        return __('Download File');
    }

    protected function isAssociativeArray($array): bool
    {
        if (!is_array($array) || empty($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    protected function makeDeleteAction(): Action
    {
        return Action::make('deleteFile')
            ->label($this->deleteLabel !== null ? $this->evaluate($this->deleteLabel) : __('Delete'))
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading($this->deleteConfirmationTitle !== null 
                ? $this->evaluate($this->deleteConfirmationTitle) 
                : __('Delete file?'))
            ->modalDescription($this->deleteConfirmationDescription !== null 
                ? $this->evaluate($this->deleteConfirmationDescription) 
                : null)
            ->action(function (array $arguments) {
                $filePathToDelete = $arguments['filePath'] ?? null;
                $filePath = $filePathToDelete ?? $this->getFilePath();
                $disk = $this->getDisk();
                $record = $this->getRecord();

                if ($this->deleteCallback) {
                    call_user_func($this->deleteCallback, $record, $filePath, $disk);
                }

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

    public function getDeleteAction(?string $filePathToDelete = null): ?Action
    {
        if (!$this->deleteCallback || !$this->showDeleteButton) {
            return null;
        }

        return $this->getAction('deleteFile')
            ->arguments(['filePath' => $filePathToDelete]);
    }

    public function getDeleteButtonLabel(): string
    {
        return $this->deleteLabel !== null ? $this->evaluate($this->deleteLabel) : __('Delete');
    }

    public function getDeleteConfirmationTitle(): string
    {
        return $this->deleteConfirmationTitle !== null 
            ? $this->evaluate($this->deleteConfirmationTitle) 
            : __('Delete file?');
    }

    public function getDeleteConfirmationDescription(): ?string
    {
        return $this->deleteConfirmationDescription !== null 
            ? $this->evaluate($this->deleteConfirmationDescription) 
            : null;
    }

    public function getDisk(): ?string
    {
        return $this->disk;
    }
}
