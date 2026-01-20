# Changelog

All notable changes to `filament-fileoutput` will be documented in this file.

## [1.1.2] - 2026-01-20

### Added
- **File descriptions** - New `description()` method to add descriptive text for files
  - Supports single string for one file
  - Supports array of descriptions for multiple files (one description per file)
  - Supports Closure with access to `$record`, `$state`, `$component`, `$get`, `$set` parameters
  - Descriptions are displayed below file names in the UI
- **Custom file labels** - Ability to set custom labels for download links via `path()` method
  - Use associative arrays where key is file path and value is custom label
  - Example: `['path/to/file.pdf' => 'Custom Label']`
  - Falls back to default "Download File" text if no label is provided
  - Works with both static arrays and dynamic Closures
  - Individual labels for each file in multiple files mode

### Changed
- `path()` method signature updated to accept `string|array|Closure` (previously `string|Closure`)
- Download link labels are now dynamic instead of hardcoded "Download File" text

### Improved
- Code formatting and style improvements
- Exception handling now uses imported `Exception` class instead of inline `\Exception`
- Better support for associative arrays in file handling
- Enhanced array detection logic to distinguish between indexed and associative arrays

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
