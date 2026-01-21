# Changelog

All notable changes to `filament-fileoutput` will be documented in this file.

## [1.1.12] - 2026-01-21

### 🎉 Release Notes

**Русский:**

Этот релиз добавляет важные улучшения в работу с удалением файлов и кастомизацией интерфейса:

**Основные изменения:**
- ✅ **Доступ к модели в callback удаления** - теперь в `onDelete()` передается объект текущей записи, что позволяет обновлять связанные поля и выполнять дополнительную логику при удалении файлов
- 🎨 **Кастомизация пустого состояния** - новый метод `emptyState()` позволяет настроить сообщение, отображаемое когда файл не загружен
- 🔧 **Исправлена регистрация Action** - модальное окно подтверждения удаления теперь корректно отображается благодаря правильной регистрации Filament Action

**⚠️ BREAKING CHANGE:**
Изменен порядок параметров в callback `onDelete()`:
- **Было:** `function ($filePath, $disk)`
- **Стало:** `function ($record, $filePath, $disk)`

Теперь первым параметром идет `$record` (объект модели), что соответствует конвенциям Filament.

**Пример использования:**
```php
FileOutput::make('document')
    ->field('document_path')
    ->disk('private')
    ->onDelete(function ($record, $filePath, $disk) {
        Storage::disk($disk)->delete($filePath);
        $record->update(['document_path' => null]);
        Log::info('File deleted', ['record_id' => $record->id]);
    })
    ->emptyState('Документ не загружен');
```

---

**English:**

This release adds important improvements to file deletion handling and UI customization:

**Key Changes:**
- ✅ **Model access in delete callback** - `onDelete()` now receives the current record object, allowing you to update related fields and perform additional logic when deleting files
- 🎨 **Custom empty state** - new `emptyState()` method allows customizing the message displayed when no file is uploaded
- 🔧 **Fixed Action registration** - delete confirmation modal now displays correctly thanks to proper Filament Action registration

**⚠️ BREAKING CHANGE:**
Changed parameter order in `onDelete()` callback:
- **Before:** `function ($filePath, $disk)`
- **Now:** `function ($record, $filePath, $disk)`

The first parameter is now `$record` (model object), following Filament conventions.

**Usage example:**
```php
FileOutput::make('document')
    ->field('document_path')
    ->disk('private')
    ->onDelete(function ($record, $filePath, $disk) {
        Storage::disk($disk)->delete($filePath);
        $record->update(['document_path' => null]);
        Log::info('File deleted', ['record_id' => $record->id]);
    })
    ->emptyState('No document uploaded');
```

### Added
- **Model parameter in delete callback** - `onDelete()` callback now receives `$record` as first parameter
- **Custom empty state** - New `emptyState()` method to customize the message displayed when no file is uploaded
  - Supports static strings and dynamic Closures
  - Default: "No file uploaded"

### Changed
- **BREAKING**: `onDelete()` callback parameter order changed to `($record, $filePath, $disk)` - `$record` is now first parameter following Filament conventions

### Fixed
- Fixed delete confirmation modal not appearing by properly registering Filament Action using `registerActions()` in `setUp()` method
- Delete action now correctly passes arguments and displays confirmation dialog

---

## [1.1.2] - 2026-01-20

### Added
- **File descriptions** - New `description()` method to add descriptive text for files
  - Supports single string for one file
  - Supports indexed array of descriptions for multiple files (one description per file)
  - Supports associative array with file path as key and description as value
  - Supports Closure with access to `$record`, `$state`, `$component`, `$get`, `$set` parameters
  - Descriptions are displayed below file names in the UI
- **Custom file labels** - Ability to set custom labels for download links via `path()` method
  - Use associative arrays where key is file path and value is custom label
  - Example: `['path/to/file.pdf' => 'Custom Label']`
  - Falls back to default "Download File" text if no label provided
  - Works with both static arrays and dynamic Closures
  - Individual labels for each file in multiple files mode
- **Custom delete confirmation** - New methods to customize the delete confirmation modal
  - `deleteLabel()` - Set custom label for the delete button
  - `deleteConfirmationTitle()` - Set custom title for confirmation modal
  - `deleteConfirmationDescription()` - Set custom description for confirmation modal
  - All methods support static strings and dynamic Closures
  - Provides better UX for critical file deletions
- **Custom empty state** - New `emptyState()` method to customize the message displayed when no file is uploaded
  - Supports static strings and dynamic Closures
  - Default: "No file uploaded"

### Changed
- `path()` method signature updated to accept `string|array|Closure` (previously `string|Closure`)
- Download link labels are now dynamic instead of hardcoded "Download File" text
- `$filePath` property type changed from `?string` to `string|array|null` to support array paths
- **BREAKING**: `onDelete()` callback parameter order changed to `($record, $filePath, $disk)` - `$record` is now first parameter following Filament conventions

### Fixed
- Fixed type error when using associative arrays in `path()` method
- Fixed "Typed property Action::$component must not be accessed before initialization" error by adding `->component($this)` call
- `description()` method now properly supports associative arrays with file path as key

### Improved
- Code formatting and style improvements
- Exception handling now uses imported `Exception` class instead of inline `\Exception`
- Better support for associative arrays in file handling
- Enhanced array detection logic to distinguish between indexed and associative arrays
- `getDescription()` method now checks for associative array keys before falling back to indexed array

## [1.0.0] - 2026-01-19

### Added
- Initial release
- FileOutput field component for displaying uploaded files
- Support for private and public storage disks
- Automatic image preview for image files
- Download link for non-image files
- **Multiple files support** - automatically detects and displays arrays of files
- Individual delete buttons for each file in multiple files mode
- `path()` method for specifying direct file path (string or Closure)
- `field()` method for reading path from database field
- Path priority: `path()` takes precedence over `field()`
- Delete button with callback support using Filament Actions
- Automatic parameter passing to delete callback ($filePath, $disk)
- Automatic field state clearing after successful deletion (clears linked FileUpload field)
- Smart array handling - removes only deleted file from array, not entire field
- `hideDeleteButton()` and `showDeleteButton()` methods for controlling delete button visibility
- Conditional delete button display based on user permissions or record state
- Temporary signed URL support for private files
- Custom download route for disks without signed URL support
- Full Filament design system integration
- Dark mode support
- Confirmation dialog for file deletion
