# Примеры использования FileOutput

## Базовый пример

```php
use Tigusigalpa\FileOutput\FileOutput;

FileOutput::make('document_preview')
    ->field('document_path')
    ->label('Текущий документ')
```

## С приватным диском

```php
FileOutput::make('avatar_preview')
    ->field('avatar')
    ->disk('private')
    ->label('Текущий аватар')
```

## С кнопкой удаления

```php
FileOutput::make('file_preview')
    ->field('file_path')
    ->disk('private')
    ->onDelete(function ($filePath, $disk) {
        // $filePath - автоматически передается значение из поля 'file_path'
        // $disk - автоматически передается значение 'private'
        
        \Illuminate\Support\Facades\Storage::disk($disk)->delete($filePath);
        
        // Можно также обновить запись в БД
        $this->record->update(['file_path' => null]);
    })
```

## Полный пример с FileUpload

```php
use Filament\Forms\Components\FileUpload;
use Tigusigalpa\FileOutput\FileOutput;
use Filament\Forms\Form;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Поле для загрузки нового файла
            FileUpload::make('document')
                ->disk('private')
                ->directory('documents')
                ->label('Загрузить документ')
                ->acceptedFileTypes(['application/pdf', 'application/msword'])
                ->maxSize(10240),
            
            // Поле для отображения текущего файла
            FileOutput::make('current_document')
                ->field('document')  // Связано с полем 'document'
                ->disk('private')
                ->label('Текущий документ')
                ->onDelete(function ($filePath, $disk) {
                    // Удаляем файл с диска
                    \Storage::disk($disk)->delete($filePath);
                    
                    // Обнуляем поле в БД
                    $this->record->update(['document' => null]);
                    
                    // ВАЖНО: Поле 'document' автоматически очищается в форме!
                    // FileUpload также автоматически обновится и покажет пустое состояние
                    
                    // Опционально: отправляем уведомление
                    \Filament\Notifications\Notification::make()
                        ->title('Документ удален')
                        ->success()
                        ->send();
                }),
        ]);
}
```

> **Автоматическая очистка состояния**: После успешного удаления файла, плагин автоматически очищает состояние связанного поля (в данном примере `document`). Это означает, что поле FileUpload также автоматически обновится и покажет пустое состояние.

## Пример с изображениями

```php
FileOutput::make('product_image_preview')
    ->field('image')
    ->disk('public')
    ->label('Изображение товара')
    ->onDelete(function ($filePath, $disk) {
        // Удаляем оригинал
        \Storage::disk($disk)->delete($filePath);
        
        // Удаляем миниатюры (если есть)
        $directory = dirname($filePath);
        $filename = basename($filePath);
        
        \Storage::disk($disk)->delete($directory . '/thumbs/' . $filename);
        
        // Обновляем запись
        $this->record->update(['image' => null]);
    })
```

## Пример с S3

```php
FileOutput::make('backup_preview')
    ->field('backup_path')
    ->disk('s3')
    ->label('Файл резервной копии')
    ->onDelete(function ($filePath, $disk) {
        \Storage::disk($disk)->delete($filePath);
        
        \Log::info('Backup deleted', [
            'path' => $filePath,
            'disk' => $disk,
            'user' => auth()->id(),
        ]);
        
        $this->record->delete();
    })
```

## Условное отображение

```php
FileOutput::make('contract_preview')
    ->field('contract_file')
    ->disk('private')
    ->label('Договор')
    ->visible(fn ($record) => $record?->contract_file !== null)
    ->onDelete(function ($filePath, $disk) {
        \Storage::disk($disk)->delete($filePath);
        $this->record->update(['contract_file' => null, 'contract_signed_at' => null]);
    })
```

## Скрытие кнопки удаления

### Полностью скрыть кнопку удаления

```php
FileOutput::make('document_preview')
    ->field('document')
    ->disk('private')
    ->label('Документ (только просмотр)')
    ->onDelete(function ($filePath, $disk) {
        \Storage::disk($disk)->delete($filePath);
    })
    ->hideDeleteButton()
```

### Условное скрытие кнопки удаления

```php
// Скрыть кнопку для заблокированных записей
FileOutput::make('file_preview')
    ->field('file_path')
    ->disk('private')
    ->onDelete(function ($filePath, $disk) {
        \Storage::disk($disk)->delete($filePath);
    })
    ->hideDeleteButton(fn ($record) => $record->is_locked)
```

### Показать кнопку только для администраторов

```php
FileOutput::make('sensitive_document')
    ->field('document_path')
    ->disk('private')
    ->onDelete(function ($filePath, $disk) {
        \Storage::disk($disk)->delete($filePath);
        $this->record->update(['document_path' => null]);
    })
    ->showDeleteButton(fn () => auth()->user()->isAdmin())
```

### Показать кнопку только владельцу

```php
FileOutput::make('user_avatar')
    ->field('avatar')
    ->disk('public')
    ->onDelete(function ($filePath, $disk) {
        \Storage::disk($disk)->delete($filePath);
        $this->record->update(['avatar' => null]);
    })
    ->showDeleteButton(fn ($record) => auth()->id() === $record->user_id)
```

### Скрыть кнопку для опубликованных документов

```php
FileOutput::make('article_image')
    ->field('featured_image')
    ->disk('public')
    ->onDelete(function ($filePath, $disk) {
        \Storage::disk($disk)->delete($filePath);
    })
    ->hideDeleteButton(fn ($record) => $record->status === 'published')
```

### Комбинированные условия

```php
FileOutput::make('invoice_pdf')
    ->field('invoice_file')
    ->disk('private')
    ->onDelete(function ($filePath, $disk) {
        \Storage::disk($disk)->delete($filePath);
        $this->record->update(['invoice_file' => null]);
    })
    ->showDeleteButton(function ($record) {
        // Показать кнопку только если:
        // 1. Пользователь - администратор ИЛИ владелец
        // 2. И счет не оплачен
        $isOwnerOrAdmin = auth()->user()->isAdmin() || 
                          auth()->id() === $record->user_id;
        $isNotPaid = $record->status !== 'paid';
        
        return $isOwnerOrAdmin && $isNotPaid;
    })
```

## Пример в таблице (Table)

```php
use Filament\Tables\Table;
use Tigusigalpa\FileOutput\FileOutput;

public static function table(Table $table): Table
{
    return $table
        ->columns([
            // ... другие колонки
        ])
        ->filters([
            // ... фильтры
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make()
                ->form([
                    FileOutput::make('attachment_preview')
                        ->field('attachment')
                        ->disk('private')
                        ->label('Вложение')
                        ->onDelete(function ($filePath, $disk) {
                            \Storage::disk($disk)->delete($filePath);
                            $this->record->update(['attachment' => null]);
                        }),
                ]),
        ]);
}
```

## Обработка ошибок

```php
FileOutput::make('file_preview')
    ->field('file_path')
    ->disk('private')
    ->onDelete(function ($filePath, $disk) {
        try {
            if (\Storage::disk($disk)->exists($filePath)) {
                \Storage::disk($disk)->delete($filePath);
                $this->record->update(['file_path' => null]);
                
                \Filament\Notifications\Notification::make()
                    ->title('Файл успешно удален')
                    ->success()
                    ->send();
            } else {
                throw new \Exception('Файл не найден');
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Ошибка при удалении файла')
                ->body($e->getMessage())
                ->danger()
                ->send();
                
            \Log::error('File deletion error', [
                'path' => $filePath,
                'disk' => $disk,
                'error' => $e->getMessage(),
            ]);
        }
    })
```
