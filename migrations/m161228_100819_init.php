<?php

class m161228_100819_init extends \yii\db\Migration
{
    use \carono\yii2migrate\traits\MigrationTrait;

    public $tableName = '{{%file_upload}}';

    public function newTables()
    {
        return [
            $this->tableName => [
                'id' => self::primaryKey(),
                'uid' => self::string(32)->unique()->comment('Уникальный идентификатор, используется для содания директорий хранения'),
                'user_id' => self::integer()->comment('Текущий пользователь, который сохраняет файл'),
                'name' => self::string()->comment('Имя файла, без расширения'),
                'extension' => self::string()->comment('Расширение'),
                'folder' => self::string()->comment('Папка, где хранится файл, можно использовать @алиасы'),
                'mime_type' => self::string()->comment('Mime Type по содержимому файла'),
                'size' => self::integer()->comment('Размер файла'),
                'data' => self::text()->comment('Произвольные данные'),
                'session' => self::string()->comment('Сессия текущего пользователя'),
                'md5' => self::string(32)->comment('MD5 по содержимому файла'),
                'slug' => self::string()->comment('Произвольный слаг'),
                'is_active' => self::boolean()->notNull()->defaultValue(true),
                'is_exist' => self::boolean()->notNull()->defaultValue(true)->comment('Существование реального файла'),
                'binary' => self::binary()->comment('Файл хранится в базе (на данный момент не используется)'),
                'created_at' => self::dateTime()->comment('Дата создания'),
                'updated_at' => self::dateTime()->comment('Дата обновления')
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
