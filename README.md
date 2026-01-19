# Filament FileOutput

A Laravel Filament plugin for displaying uploaded files, including private files that cannot be displayed with the standard FileUpload field.

## Features

- Display uploaded files from any storage disk (public, private, s3, etc.)
- Automatic image preview for image files
- Download link for non-image files
- Support for private file URLs with temporary signed URLs
- Delete button with callback support
- Fully styled with Filament's design system
- Dark mode support

## Installation

Install the package via composer:

```bash
composer require tigusigalpa/filament-fileoutput
```

The package will automatically register its service provider.

## Usage

### Basic Usage

```php
use Tigusigalpa\FileOutput\FileOutput;

FileOutput::make('file_preview')
    ->field('file_path')
```

### With Disk Specification

```php
FileOutput::make('file_preview')
    ->disk('private')
    ->field('document')
```

### With Delete Callback

```php
FileOutput::make('file_preview')
    ->field('avatar')
    ->disk('private')
    ->onDelete(function ($filePath, $disk) {
        // $filePath - путь к файлу из поля 'avatar'
        // $disk - название диска ('private' в данном случае)
        Storage::disk($disk)->delete($filePath);
    })
```

### Complete Example in a Form

```php
use Filament\Forms\Components\FileUpload;
use Tigusigalpa\FileOutput\FileOutput;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            FileUpload::make('document')
                ->disk('private')
                ->directory('documents')
                ->label('Upload Document'),
                
            FileOutput::make('document_preview')
                ->field('document')  // Связано с полем 'document'
                ->disk('private')
                ->label('Current Document')
                ->onDelete(function ($filePath, $disk) {
                    // Удаляем файл с диска
                    Storage::disk($disk)->delete($filePath);
                    // Поле 'document' автоматически очищается после успешного удаления
                }),
        ]);
}
```

> **Note**: When a file is successfully deleted, the plugin automatically clears the state of the linked field (in this example, the `document` field). This means the FileUpload field will also be cleared automatically.

## Parameters

### `field(string $fieldName)` - Required

Specifies the field name to read the file path from. This should match the database column or model attribute containing the file path.

```php
FileOutput::make('preview')
    ->field('file_path')
```

### `disk(?string $disk)` - Optional

Specifies the storage disk where the file is located. If not specified, the default disk will be used.

```php
FileOutput::make('preview')
    ->field('file_path')
    ->disk('private')
```

### `onDelete(?Closure $callback)` - Optional

Adds a delete button with a callback function. The callback receives two parameters:
- `$filePath` - путь к файлу из указанного поля
- `$disk` - название диска (если указан через метод `disk()`)

```php
FileOutput::make('preview')
    ->field('file_path')
    ->disk('private')
    ->onDelete(function ($filePath, $disk) {
        // $filePath содержит значение из поля 'file_path'
        // $disk содержит 'private'
        Storage::disk($disk)->delete($filePath);
    })
```

### `hideDeleteButton(bool $condition = true)` - Optional

Hides the delete button. Useful when you want to show the file but prevent deletion based on certain conditions.

```php
FileOutput::make('preview')
    ->field('file_path')
    ->disk('private')
    ->onDelete(function ($filePath, $disk) {
        Storage::disk($disk)->delete($filePath);
    })
    ->hideDeleteButton()
```

Or conditionally:

```php
FileOutput::make('preview')
    ->field('file_path')
    ->onDelete(function ($filePath, $disk) {
        Storage::disk($disk)->delete($filePath);
    })
    ->hideDeleteButton(fn ($record) => $record->is_locked)
```

### `showDeleteButton(bool $condition = true)` - Optional

Shows the delete button (default behavior). Can be used to conditionally show the button.

```php
FileOutput::make('preview')
    ->field('file_path')
    ->onDelete(function ($filePath, $disk) {
        Storage::disk($disk)->delete($filePath);
    })
    ->showDeleteButton(fn ($record) => auth()->user()->can('delete', $record))
```

## How It Works

1. **Image Files**: If the file is an image (jpg, jpeg, png, gif, bmp, svg, webp, ico), it will be displayed as an `<img>` tag with a preview.

2. **Non-Image Files**: For other file types, a download link will be displayed with the filename.

3. **Private Files**: For private disks, the plugin will attempt to generate a temporary signed URL. If that's not supported by the disk driver, it will use a custom download route.

4. **Public URLs**: If the path is already a public URL, it will be used directly.

5. **Auto State Clearing**: After successful file deletion, the plugin automatically clears the state of the linked field (specified via `field()` method). This ensures that any associated FileUpload field is also cleared, preventing stale data.

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x
- Filament 3.x or 4.x

## Author

**Igor Sazonov**  
Telegram: [@igoravel](https://t.me/igoravel)

## License

MIT License

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Repository

[https://github.com/tigusigalpa/filament-fileoutput](https://github.com/tigusigalpa/filament-fileoutput)
