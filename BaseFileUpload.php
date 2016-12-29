<?php

namespace carono\yii2file;

use Yii;

/**
 * This is the model class for table "file_upload".
 *
 * @property integer $id
 * @property string $uid
 * @property integer $user_id
 * @property string $name
 * @property string $extension
 * @property string $folder
 * @property string $mime_type
 * @property integer $size
 * @property string $data
 * @property string $session
 * @property string $md5
 * @property string $slug
 * @property integer $is_active
 * @property integer $is_exist
 * @property resource $binary
 * @property string $created_at
 * @property string $updated_at
 */
class BaseFileUpload extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'file_upload';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'size', 'is_active', 'is_exist'], 'integer'],
            [['data', 'binary'], 'string'],
            [['created_at', 'updated_at'], 'safe'],
            [['uid'], 'string', 'max' => 64],
            [['name', 'extension', 'folder', 'mime_type', 'session', 'slug'], 'string', 'max' => 255],
            [['md5'], 'string', 'max' => 32],
            [['uid'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'user_id' => 'User ID',
            'name' => 'Name',
            'extension' => 'Extension',
            'folder' => 'Folder',
            'mime_type' => 'Mime Type',
            'size' => 'Size',
            'data' => 'Data',
            'session' => 'Session',
            'md5' => 'Md5',
            'slug' => 'Slug',
            'is_active' => 'Is Active',
            'is_exist' => 'Is Exist',
            'binary' => 'Binary',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
