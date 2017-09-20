Установка
=========
1. Создать миграцию и наследовать её от `carono\yii2file\FileUploadMigration`
```php
class m170927_171858_fu extends \carono\yii2file\FileUploadMigration
{
    public $tableName = '{{%file_upload}}';
}
```

2. Или выполнить `yii migrate --migrationPath=@vendor/carono/yii2-file-upload/migrations`

Как использовать
================
1. В вашу модель таблицы `file_upload` добавить трейт `carono\yii2file\FileUploadTrait`  
2. После этого можно сохранять файлы следующим образом:
`FileUpload::startUpload('@runtime/test.txt')->process();`   
`FileUpload::startUpload('http://example.com/file.txt')->process();`  
`FileUpload::startUpload(yii\web\UploadedFile $file)->process();`  

По цепочке можно добавлять дополнительные свойства  

```php
FileUpload::startUpload('@runtime/img.png')
->slug('user_avatar') // поле slug
->data(['id'=>1]) // произвольные данные, записываются в data как json
->name('user_avatar.png') // сохраним файл с новым именем
->folder('@app/new_destination') // сохраним файл в новой папке, по умолчанию @app/files
->delete(false) // не удалять файл источник, по завершению, по умолчанию - удаляем
->process(); // сохраним модель
```

Свойства трейта
===============
|Свойство|Значение|Описание
|-----|--------|---------
|$fileNameAsUid|true|Реальный файл хранить как `uid.extension` (bb1fe78c3b769eee34202da2ac1e89c8.txt), иначе как `fileName.extension` (test.txt)
|$eraseOnDelete|true|при вызове delete(), удалять реальный файл
|$fileUploadFolder|@app/files|папка хранения файлов

Методы трейта
=============
|Метод|Описание
|-----|--------
|startUpload($file)|начать загрузку файла
|deleteFile()|удалить реальный файл
|getRealFileName()|получить имя файла, которое хранится в FS
|getRealFilePath()|полный путь реального файла
|fileExist()|проверка существования файла
|isImage()|файл по mimeType является картинкой
|getFileName()|имя файла, которое записано в базе (user_avatar.png, а в FS хранится как bb1fe78c3b769eee34202da2ac1e89c8.png)


Особенности
===========
Необходимо учесть, что при переопределение функции getRealFileName(), будет влиять и на имя при сохранении новых файлов
подробнее смотрите `carono\yii2file\Uploader`

Не забудьте добавить поведение `yii\behaviors\TimestampBehavior` для хранения времени добавления
