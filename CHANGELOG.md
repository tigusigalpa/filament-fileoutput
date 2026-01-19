# Changelog

All notable changes to `filament-fileoutput` will be documented in this file.

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
