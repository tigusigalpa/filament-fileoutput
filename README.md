<div align="center">

# 📁 Filament FileOutput

### *Display uploaded files beautifully in Filament forms*

![Laravel Filament FileOutput](https://github.com/user-attachments/assets/031bf175-15e7-4697-98ee-c2385a99b3bd)

[![Latest Version](https://img.shields.io/packagist/v/tigusigalpa/filament-fileoutput.svg?style=flat-square)](https://packagist.org/packages/tigusigalpa/filament-fileoutput)
[![Total Downloads](https://img.shields.io/packagist/dt/tigusigalpa/filament-fileoutput.svg?style=flat-square)](https://packagist.org/packages/tigusigalpa/filament-fileoutput)
[![License](https://img.shields.io/packagist/l/tigusigalpa/filament-fileoutput.svg?style=flat-square)](https://packagist.org/packages/tigusigalpa/filament-fileoutput)

A powerful Laravel Filament plugin for displaying uploaded files with support for **private storage**, **multiple files
**, and **smart deletion**.

[Installation](#-installation) • [Quick Start](#-quick-start) • [Features](#-features) • [Examples](#-examples) • [API Reference](#-api-reference)

</div>

---

## ✨ Features

<table>
<tr>
<td width="50%">

### 🎯 Core Features

- 📦 **Any Storage Disk** - public, private, S3, etc.
- 🖼️ **Smart Preview** - automatic image detection
- 📥 **Download Links** - for non-image files
- 🔒 **Private Files** - temporary signed URLs
- 🗑️ **Delete Action** - with callback support

</td>
<td width="50%">

### 🚀 Advanced Features

- 📚 **Multiple Files** - array support out of the box
- 🎨 **Filament Design** - fully styled components
- 🌙 **Dark Mode** - complete theme support
- ⚡ **Auto State Sync** - smart form updates
- 🔐 **Conditional Actions** - permission-based controls

</td>
</tr>
</table>

## 📦 Installation

Install via Composer:

```bash
composer require tigusigalpa/filament-fileoutput
```

> 🎉 **That's it!** The package auto-registers its service provider.

## 🚀 Quick Start

### Basic Usage

```php
use Tigusigalpa\FileOutput\FileOutput;

FileOutput::make('file_preview')
    ->field('file_path')
    ->label('Current File')
```

### With Private Storage

```php
FileOutput::make('document_preview')
    ->field('document')
    ->disk('private')
    ->label('Private Document')
```

### Complete Form Example

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
                ->field('document')
                ->disk('private')
                ->label('Current Document')
                ->onDelete(function ($filePath, $disk) {
                    Storage::disk($disk)->delete($filePath);
                }),
        ]);
}
```

> 💡 **Auto-Sync**: When a file is deleted, the linked field state is automatically cleared!

---

## 📚 Examples

### 🎯 Using `path()` Method

#### Direct Path

```php
FileOutput::make('contract')
    ->path('contracts/2024/contract-001.pdf')
    ->disk('private')
    ->label('Contract')
```

#### Dynamic Path with Closure

```php
FileOutput::make('user_avatar')
    ->path(fn ($record) => 'avatars/' . $record->user_id . '.jpg')
    ->disk('public')
    ->label('User Avatar')
```

#### Public URL

```php
FileOutput::make('external_file')
    ->path('https://example.com/files/document.pdf')
    ->label('External Document')
```

#### Conditional Logic

```php
FileOutput::make('file_preview')
    ->path(function ($record) {
        if ($record->file_type === 'contract') {
            return 'contracts/' . $record->file_name;
        }
        return 'documents/' . $record->file_name;
    })
    ->disk('private')
```

### 📦 Multiple Files

#### Basic Multiple Files

```php
FileOutput::make('attachments_preview')
    ->field('attachments')  // Array of file paths
    ->disk('private')
    ->label('Attachments')
```

#### Complete Multiple Files Example

```php
FileUpload::make('attachments')
    ->disk('private')
    ->directory('attachments')
    ->multiple()
    ->maxFiles(10)
    ->acceptedFileTypes(['application/pdf', 'image/*']),
    
FileOutput::make('attachments_preview')
    ->field('attachments')
    ->disk('private')
    ->label('Current Attachments')
    ->onDelete(function ($filePath, $disk) {
        Storage::disk($disk)->delete($filePath);
        // Auto-updates array - removes only this file
    })
```

#### Multiple Files via `path()` with Array

```php
FileOutput::make('documents')
    ->path(['contracts/file1.pdf', 'contracts/file2.pdf'])
    ->disk('private')
    ->onDelete(function ($filePath, $disk) {
        Storage::disk($disk)->delete($filePath);
    })
```

#### Multiple Files with Closure

```php
FileOutput::make('photos_preview')
    ->path(fn ($record) => $record->photos ?? [])
    ->field('photos')  // ⚠️ Important for auto-sync
    ->disk('public')
    ->onDelete(function ($filePath, $disk) {
        Storage::disk($disk)->delete($filePath);
        
        // Update database
        $photos = array_values(array_filter(
            $this->record->photos ?? [],
            fn($p) => $p !== $filePath
        ));
        $this->record->update(['photos' => $photos]);
    })
```

> ⚠️ **Important**: When using `path()` with arrays, also specify `field()` to enable automatic state updates!

### 🗑️ Delete Button Control

#### Hide Delete Button

```php
FileOutput::make('document')
    ->field('document')
    ->disk('private')
    ->onDelete(fn ($filePath, $disk) => Storage::disk($disk)->delete($filePath))
    ->hideDeleteButton()
```

#### Conditional Delete Button

```php
// Hide for locked records
FileOutput::make('file')
    ->field('file_path')
    ->onDelete(fn ($filePath, $disk) => Storage::disk($disk)->delete($filePath))
    ->hideDeleteButton(fn ($record) => $record->is_locked)
```

#### Show Only for Admins

```php
FileOutput::make('sensitive_doc')
    ->field('document')
    ->disk('private')
    ->onDelete(fn ($filePath, $disk) => Storage::disk($disk)->delete($filePath))
    ->showDeleteButton(fn () => auth()->user()->isAdmin())
```

#### Complex Conditions

```php
FileOutput::make('invoice')
    ->field('invoice_file')
    ->onDelete(fn ($filePath, $disk) => Storage::disk($disk)->delete($filePath))
    ->showDeleteButton(function ($record) {
        $isOwnerOrAdmin = auth()->user()->isAdmin() || 
                          auth()->id() === $record->user_id;
        $isNotPaid = $record->status !== 'paid';
        
        return $isOwnerOrAdmin && $isNotPaid;
    })
```

### 🖼️ Image Gallery Example

```php
FileOutput::make('product_images')
    ->field('images')
    ->disk('public')
    ->label('Product Gallery')
    ->onDelete(function ($filePath, $disk) {
        // Delete original
        Storage::disk($disk)->delete($filePath);
        
        // Delete thumbnails
        $directory = dirname($filePath);
        $filename = basename($filePath);
        Storage::disk($disk)->delete($directory . '/thumbs/' . $filename);
    })
```

### ☁️ S3 Storage Example

```php
FileOutput::make('backup')
    ->field('backup_path')
    ->disk('s3')
    ->label('Backup File')
    ->onDelete(function ($filePath, $disk) {
        Storage::disk($disk)->delete($filePath);
        
        Log::info('Backup deleted', [
            'path' => $filePath,
            'user' => auth()->id(),
        ]);
    })
```

### 🔔 With Notifications

```php
FileOutput::make('document')
    ->field('document')
    ->disk('private')
    ->onDelete(function ($filePath, $disk) {
        try {
            if (Storage::disk($disk)->exists($filePath)) {
                Storage::disk($disk)->delete($filePath);
                
                Notification::make()
                    ->title('File deleted successfully')
                    ->success()
                    ->send();
            } else {
                throw new \Exception('File not found');
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error deleting file')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
            Log::error('File deletion error', [
                'path' => $filePath,
                'error' => $e->getMessage(),
            ]);
        }
    })
```

### 🎭 Conditional Visibility

```php
FileOutput::make('contract')
    ->field('contract_file')
    ->disk('private')
    ->visible(fn ($record) => $record?->contract_file !== null)
    ->onDelete(function ($filePath, $disk) {
        Storage::disk($disk)->delete($filePath);
        $this->record->update([
            'contract_file' => null,
            'contract_signed_at' => null,
        ]);
    })
```

### 📊 Advanced Multiple Files

#### Limit Deletion (Keep at Least One)

```php
FileOutput::make('documents')
    ->field('documents')
    ->disk('private')
    ->onDelete(fn ($filePath, $disk) => Storage::disk($disk)->delete($filePath))
    ->showDeleteButton(function ($record) {
        $documents = $record->documents ?? [];
        return is_array($documents) && count($documents) > 1;
    })
```

#### With Database Counter Update

```php
FileOutput::make('certificates')
    ->field('certificates')
    ->disk('private')
    ->onDelete(function ($filePath, $disk) {
        Storage::disk($disk)->delete($filePath);
        
        $certificates = array_values(array_filter(
            $this->record->certificates ?? [],
            fn($cert) => $cert !== $filePath
        ));
        
        $this->record->update([
            'certificates' => empty($certificates) ? null : $certificates,
            'certificates_count' => count($certificates),
        ]);
        
        Notification::make()
            ->title('Certificate deleted')
            ->body('Remaining: ' . count($certificates))
            ->success()
            ->send();
    })
```

---

## 📖 API Reference

### Methods

#### `field(string $fieldName)`

**Required** (if `path` not specified)

Specifies the field name to read the file path from.

```php
FileOutput::make('preview')->field('file_path')
```

#### `path(string|array|Closure $path)`

**Required** (if `field` not specified)

Specifies the direct path to the file. Supports:

- **String**: Direct file path
- **Array**: Multiple file paths
- **Closure**: Dynamic path (can return string or array)
- **Public URL**: External file URL

```php
// String
->path('documents/report.pdf')

// Array
->path(['file1.pdf', 'file2.pdf'])

// Closure (single)
->path(fn ($record) => 'users/' . $record->user_id . '/avatar.jpg')

// Closure (multiple)
->path(fn ($record) => $record->files ?? [])

// Public URL
->path('https://example.com/file.pdf')
```

> 💡 **Priority**: `path()` takes priority for reading, but `field()` is still used for auto-updates.
>
> ⚠️ **Best Practice**: When using `path()` with arrays, also specify `field()` for automatic state updates:
> ```php
> ->path(fn ($record) => $record->files ?? [])
> ->field('files')  // Enables auto-sync
> ```

#### `disk(string $disk)`

**Optional**

Specifies the storage disk (public, private, s3, etc.).

```php
->disk('private')
```

#### `onDelete(Closure $callback)`

**Optional**

Adds delete button with callback. Receives `$filePath` and `$disk` parameters.

```php
->onDelete(function ($filePath, $disk) {
    Storage::disk($disk)->delete($filePath);
})
```

> 🔄 **Auto-Sync**: Field state is automatically cleared after deletion (if `field()` is specified).

#### `hideDeleteButton(bool|Closure $condition = true)`

**Optional**

Hides the delete button.

```php
// Always hide
->hideDeleteButton()

// Conditional
->hideDeleteButton(fn ($record) => $record->is_locked)
```

#### `showDeleteButton(bool|Closure $condition = true)`

**Optional**

Shows the delete button (default). Useful for conditional display.

```php
->showDeleteButton(fn () => auth()->user()->isAdmin())
```

---

## 🔧 How It Works

<table>
<tr>
<td width="33%">

### 🖼️ Images

Automatic detection of image files (jpg, jpeg, png, gif, bmp, svg, webp, ico) with preview display.

</td>
<td width="33%">

### 📄 Documents

Download links for non-image files with filename display.

</td>
<td width="33%">

### 🔒 Private Files

Temporary signed URLs or custom download routes for secure access.

</td>
</tr>
<tr>
<td width="33%">

### 🌐 Public URLs

Direct display of external file URLs.

</td>
<td width="33%">

### 📚 Multiple Files

Automatic array detection with individual previews and delete buttons.

</td>
<td width="33%">

### 🔄 Auto-Sync

Smart state updates - removes only deleted files from arrays.

</td>
</tr>
</table>

---

## ⚙️ Requirements

- **PHP**: 8.1 or higher
- **Laravel**: 10.x, 11.x, or 12.x
- **Filament**: 3.x or 4.x

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📝 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

---

## 👨‍💻 Author

**Igor Sazonov**

- Telegram: [@igoravel](https://t.me/igoravel)
- GitHub: [@tigusigalpa](https://github.com/tigusigalpa)

---

## 🔗 Links

- **Repository**: [github.com/tigusigalpa/filament-fileoutput](https://github.com/tigusigalpa/filament-fileoutput)
- **Packagist
  **: [packagist.org/packages/tigusigalpa/filament-fileoutput](https://packagist.org/packages/tigusigalpa/filament-fileoutput)
- **Issues**: [Report a bug](https://github.com/tigusigalpa/filament-fileoutput/issues)

---

<div align="center">

**If you find this package helpful, please consider giving it a ⭐ on GitHub!**

Made with ❤️ for the Filament community

</div>
