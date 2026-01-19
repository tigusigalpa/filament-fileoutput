# Примеры использования FileOutput

## Базовый пример

```php
use Tigusigalpa\FileOutput\FileOutput;

FileOutput::make('document_preview')
    ->field('document_path')
    ->label('Текущий документ')
```

## Использование метода path()

### Прямой путь к файлу

```php
FileOutput::make('contract_preview')
    ->path('contracts/2024/contract-001.pdf')
    ->disk('private')
    ->label('Договор')
```

### Динамический путь через Closure

```php
FileOutput::make('user_avatar')
    ->path(fn ($record) => 'avatars/' . $record->user_id . '.jpg')
    ->disk('public')
    ->label('Аватар пользователя')
```

### Публичный URL

```php
FileOutput::make('external_file')
    ->path('https://example.com/files/document.pdf')
    ->label('Внешний документ')
```

### Комбинация с условной логикой

```php
FileOutput::make('file_preview')
    ->path(function ($record) {
        if ($record->file_type === 'contract') {
            return 'contracts/' . $record->file_name;
        }
        return 'documents/' . $record->file_name;
    })
    ->disk('private')
    ->label('Документ')
```

### Множественные файлы через path() с массивом

```php
// Прямой массив путей
FileOutput::make('documents_preview')
    ->path(['contracts/2024/contract-1.pdf', 'contracts/2024/contract-2.pdf'])
    ->disk('private')
    ->label('Договоры')
    ->onDelete(function ($filePath, $disk) {
        // $filePath - конкретный файл из массива, который удаляется
        \Storage::disk($disk)->delete($filePath);
    })
```

### Множественные файлы через path() с Closure

```php
FileOutput::make('attachments_preview')
    ->path(fn ($record) => $record->attachment_paths ?? [])
    ->field('attachment_paths')  // ВАЖНО: указать field для автообновления
    ->disk('private')
    ->label('Вложения')
    ->onDelete(function ($filePath, $disk) {
        // $filePath - это ОДИН конкретный файл, который пользователь удаляет
        \Storage::disk($disk)->delete($filePath);
        
        // Плагин автоматически обновит поле 'attachment_paths', удалив только этот файл
        // Обновлять БД вручную не нужно, если указан field()
    })
```

### Важно: onDelete для множественных файлов

```php
// ✅ ВАРИАНТ 1: С field() - автоматическое обновление состояния
FileOutput::make('photos_preview')
    ->path(fn ($record) => $record->photos ?? [])
    ->field('photos')  // Указываем field для автообновления
    ->disk('public')
    ->onDelete(function ($filePath, $disk) {
        // $filePath - это один конкретный файл
        \Storage::disk($disk)->delete($filePath);
        
        // Обновление БД
        $currentPhotos = $this->record->photos ?? [];
        $newPhotos = array_values(array_filter(
            $currentPhotos,
            fn($photo) => $photo !== $filePath
        ));
        $this->record->update(['photos' => $newPhotos]);
        
        // Состояние формы обновится автоматически благодаря field('photos')
    })

// ✅ ВАРИАНТ 2: Без field() - только path()
FileOutput::make('photos_preview')
    ->path(fn ($record) => $record->photos ?? [])
    ->disk('public')
    ->onDelete(function ($filePath, $disk) {
        // $filePath - это один конкретный файл
        \Storage::disk($disk)->delete($filePath);
        
        // Обновляем БД
        $currentPhotos = $this->record->photos ?? [];
        $newPhotos = array_values(array_filter(
            $currentPhotos,
            fn($photo) => $photo !== $filePath
        ));
        $this->record->update(['photos' => $newPhotos]);
        
        // ⚠️ ВНИМАНИЕ: Состояние формы НЕ обновится автоматически
        // Нужно вручную обновить или перезагрузить страницу
    })
```

## Множественные файлы

### Базовый пример с множественными файлами

```php
FileOutput::make('attachments_preview')
    ->field('attachments')  // Поле содержит массив путей к файлам
    ->disk('private')
    ->label('Вложения')
```

### Полный пример с FileUpload (множественные файлы)

```php
use Filament\Forms\Components\FileUpload;
use Tigusigalpa\FileOutput\FileOutput;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            FileUpload::make('attachments')
                ->disk('private')
                ->directory('attachments')
                ->multiple()  // Разрешить множественные файлы
                ->maxFiles(10)
                ->acceptedFileTypes(['application/pdf', 'image/*'])
                ->label('Загрузить вложения'),
                
            FileOutput::make('attachments_preview')
                ->field('attachments')  // Автоматически определяет массив
                ->disk('private')
                ->label('Текущие вложения')
                ->onDelete(function ($filePath, $disk) {
                    // $filePath содержит путь к конкретному удаляемому файлу
                    \Storage::disk($disk)->delete($filePath);
                    // Состояние поля автоматически обновляется (файл удаляется из массива)
                }),
        ]);
}
```

### Множественные изображения с галереей

```php
FileOutput::make('product_images')
    ->field('images')
    ->disk('public')
    ->label('Галерея товара')
    ->onDelete(function ($filePath, $disk) {
        // Удаляем оригинал
        \Storage::disk($disk)->delete($filePath);
        
        // Удаляем миниатюры
        $directory = dirname($filePath);
        $filename = basename($filePath);
        \Storage::disk($disk)->delete($directory . '/thumbs/' . $filename);
    })
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

## Продвинутые примеры с множественными файлами

### Ограничение удаления для множественных файлов

```php
FileOutput::make('documents_preview')
    ->field('documents')
    ->disk('private')
    ->onDelete(function ($filePath, $disk) {
        \Storage::disk($disk)->delete($filePath);
    })
    ->showDeleteButton(function ($record) {
        // Показать кнопку удаления только если файлов больше одного
        $documents = $record->documents ?? [];
        return is_array($documents) && count($documents) > 1;
    })
```

### Множественные файлы с обновлением БД

```php
FileOutput::make('certificates_preview')
    ->field('certificates')
    ->disk('private')
    ->label('Сертификаты')
    ->onDelete(function ($filePath, $disk) {
        // Удаляем файл
        \Storage::disk($disk)->delete($filePath);
        
        // Обновляем массив в БД
        $currentCertificates = $this->record->certificates ?? [];
        $newCertificates = array_values(array_filter(
            $currentCertificates, 
            fn($cert) => $cert !== $filePath
        ));
        
        $this->record->update([
            'certificates' => empty($newCertificates) ? null : $newCertificates,
            'certificates_count' => count($newCertificates),
        ]);
        
        \Filament\Notifications\Notification::make()
            ->title('Сертификат удален')
            ->body('Осталось сертификатов: ' . count($newCertificates))
            ->success()
            ->send();
    })
```

### Смешанный тип файлов (изображения и документы)

```php
FileOutput::make('mixed_files_preview')
    ->field('files')
    ->disk('private')
    ->label('Файлы проекта')
    ->onDelete(function ($filePath, $disk) {
        \Storage::disk($disk)->delete($filePath);
        
        // Логирование удаления
        \Log::info('Project file deleted', [
            'project_id' => $this->record->id,
            'file' => $filePath,
            'user_id' => auth()->id(),
        ]);
    })
```
