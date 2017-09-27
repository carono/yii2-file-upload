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
                'uid' => self::string(32)->unique(),
                'user_id' => self::integer(),
                'name' => self::string(),
                'extension' => self::string(),
                'folder' => self::string(),
                'mime_type' => self::string(),
                'size' => self::integer(),
                'data' => self::text(),
                'session' => self::string(),
                'md5' => self::string(32),
                'slug' => self::string(),
                'is_active' => self::boolean()->notNull()->defaultValue(true),
                'is_exist' => self::boolean()->notNull()->defaultValue(true),
                'binary' => self::binary(),
                'created_at' => self::dateTime(),
                'updated_at' => self::dateTime()
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
