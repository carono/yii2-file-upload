<?php


namespace carono\yii2file;

use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use carono\yii2file\Uploader;

/**
 * Trait FileUploadTrait
 *
 * @package carono\yii2file
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
 *
 * @property string $fileName
 * @property string $realFileName
 * @property string $realFilePath
 *
 * @mixin ActiveRecord
 */
trait FileUploadTrait
{
    public $fileNameAsUid = true;
    public $eraseOnDelete = true;
    public $uploaderClass = Uploader::class;
    public $fileUploadFolder = '@app/files';

    public function init()
    {
        $this->on(self::EVENT_BEFORE_DELETE, [$this, 'eraseOnDelete']);
        parent::init();
    }

    /**
     * @param $file
     * @return Uploader|mixed
     */
    public static function startUpload($file)
    {
        $model = new static();
        return \Yii::createObject([
            'class' => $model->uploaderClass,
            'modelClass' => static::class,
            'file' => $file,
            'folder' => $model->fileUploadFolder,
        ]);
    }

    /**
     * @return bool
     */
    public function deleteFile()
    {
        if ($this->fileExist()) {
            unlink($this->getRealFilePath());
            if ($f = !$this->fileExist()) {
                $this->updateAttributes(['is_exist' => false]);
            }
            return $f;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getRealFileName()
    {
        if ($this->fileNameAsUid) {
            return $this->uid . '.' . $this->extension;
        }

        return $this->name . '.' . $this->extension;
    }

    /**
     * @return mixed
     */
    public function getRealFilePath()
    {
        $path = \Yii::getAlias($this->folder) . DIRECTORY_SEPARATOR . $this->getRealFileName();
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @return bool
     */
    public function fileExist()
    {
        return file_exists($this->getRealFilePath());
    }

    /**
     * @return bool|null
     */
    public function isImage()
    {
        if (($mime = $this->mime_type) || ($mime = FileHelper::getMimeType($this->getRealFilePath()))) {
            return strpos($mime, 'image') === 0;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return implode('.', array_filter([$this->name, $this->extension]));
    }

    public function eraseOnDelete()
    {
        if ($this->eraseOnDelete && $this->fileExist()) {
            $this->deleteFile();
        }
    }
}