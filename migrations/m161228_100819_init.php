<?php

class m161228_100819_init extends \yii\db\Migration
{
    use \carono\yii2migrate\traits\MigrationTrait;

    public $tableName = '{{%file_upload}}';

    public function newTables()
    {
        return [
            $this->tableName => [
                'id' => $this->primaryKey(),
                'uid' => $this->string(32)->unique()->comment('Уникальный идентификатор, используется для содания директорий хранения'),
                'user_id' => $this->integer()->comment('Текущий пользователь, который сохраняет файл'),
                'name' => $this->string()->comment('Имя файла, без расширения'),
                'extension' => $this->string()->comment('Расширение'),
                'folder' => $this->string()->comment('Папка, где хранится файл, можно использовать @алиасы'),
                'mime_type' => $this->string()->comment('Mime Type по содержимому файла'),
                'size' => $this->integer()->comment('Размер файла'),
                'data' => $this->text()->comment('Произвольные данные'),
                'session' => $this->string()->comment('Сессия текущего пользователя'),
                'md5' => $this->string(32)->comment('MD5 по содержимому файла'),
                'slug' => $this->string()->comment('Произвольный слаг'),
                'is_active' => $this->boolean()->notNull()->defaultValue(true),
                'is_exist' => $this->boolean()->notNull()->defaultValue(true)->comment('Существование реального файла'),
                'binary' => $this->binary()->comment('Файл хранится в базе (на данный момент не используется)'),
                'created_at' => $this->dateTime()->comment('Дата создания'),
                'updated_at' => $this->dateTime()->comment('Дата обновления')
            ]
        ];
    }

    public function newIndex()
    {
        return [
            [$this->tableName, ['is_active', 'is_exist']],
            [$this->tableName, 'slug'],
        ];
    }

    public function safeUp()
    {
        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        } else {
            $tableOptions = null;
        }
        $this->upNewTables([], $tableOptions);
        $this->upNewIndex();
    }

    public function safeDown()
    {
        $this->downNewTables();
    }
}
