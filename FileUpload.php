<?php

namespace carono\yii2file;

use Yii;
use yii\base\Security;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * Class FileUpload
 *
 * @package carono\components
 * @property string $fullName
 */
class FileUpload extends BaseFileUpload
{
    const F_FILES = '@app/files';

    public $eraseOnDelete = true;

    public function behaviors()
    {
        return [
            'timestamp' => [
                'class'      => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
                    ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value'      => new Expression('NOW()'),
            ],
        ];
    }

    public function beforeDelete()
    {
        if ($this->eraseOnDelete && $this->fileExist()) {
            $this->deleteFile();
        }
        return parent::beforeDelete();
    }

    public static function upload($file, $name = null, $dir = self::F_FILES, $slug = null, $data = null, $delete = true)
    {
        if (is_null($dir)) {
            $dir = self::F_FILES;
        }
        $dir = trim($dir);
        $filePath = '';
        if (strpos($file, 'http') === 0) {
            $tmp = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . uniqid("fu");
            file_put_contents($tmp, file_get_contents($file));
            $filePath = $tmp;
            $name = $name ? $name : basename($file);
        } elseif (is_string($file)) {
            $filePath = Yii::getAlias($file);
        } elseif ($file instanceof UploadedFile) {
            $filePath = $file->tempName;
            $name = $name ? $name : $file->name;
        }
        $name = $name ? $name : basename($filePath);
        $sec = new Security();
        while (FileUpload::find()->where(["uid" => $uPath = strtolower($sec->generateRandomString(64))])->exists()) {
        }
        $dirSlug = $dir;
        if (!is_dir($dir) && (!$dir = self::getFolder($dirSlug))) {
            if (!$dir) {
                throw new \Exception("Folder for param '$dirSlug' is not set");
            } else {
                throw new \Exception("Folder '$dir' not found");
            }
        }
        $fullPath = self::formPath($uPath, $dir);
        if (!FileHelper::createDirectory(dirname($fullPath))) {
            throw new \Exception("Can't create folder '{" . dirname($fullPath) . "}'");
        }
        if (!file_exists($filePath)) {
            throw new \Exception('File not loaded or not exist');
        }
        if (is_uploaded_file($filePath)) {
            if (!move_uploaded_file($filePath, $fullPath)) {
                throw new \Exception('Unknown upload error');
            }
        } elseif ($delete ? !rename($filePath, $fullPath) : !copy($filePath, $fullPath)) {
            throw new \Exception('Failed to write file to disk');
        }
        $fileUpload = new self();

        if (isset(Yii::$app->session)) {
            Yii::$app->session->open();
            $fileUpload->session = Yii::$app->session->getIsActive() ? Yii::$app->session->getId() : null;
            Yii::$app->session->close();
        }
        if (isset(Yii::$app->user)) {
            $fileUpload->user_id = Yii::$app->user->getId();
        }
        $fileUpload->data = !is_null($data) ? json_encode($data) : null;
        $fileUpload->mime_type = FileHelper::getMimeType($fullPath);
        $fileUpload->md5 = md5_file($fullPath);
        $fileUpload->folder = $dirSlug;
        $fileUpload->uid = $uPath;
        $fileUpload->slug = $slug;
        $fileUpload->size = filesize($fullPath);
        if (!$extension = strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
            $extension = ArrayHelper::getValue(FileHelper::getExtensionsByMimeType($fileUpload->mime_type), 0);
        }
        $fileUpload->name = basename($name, '.' . $extension);
        $fileUpload->extension = $extension;
        if ($fileUpload->save()) {
            return $fileUpload;
        } else {
            $fileUpload->deleteFile();
            return null;
        }
    }

    public function isImage()
    {
        if (($mime = $this->mime_type) || ($mime = FileHelper::getMimeType($this->getFullPath()))) {
            return strpos($mime, 'image') === 0;
        } else {
            return null;
        }
    }

    public function getFullPath()
    {
        return self::formPath($this->uid, self::getFolder($this->folder));
    }

    public function fileExist()
    {
        return file_exists($this->getFullPath());
    }

    public function deleteFile()
    {
        if ($this->fileExist()) {
            @unlink($this->getFullPath());
            if ($f = !$this->fileExist()) {
                $this->updateAttributes(["is_exist" => false, "is_active" => false]);
            }
            return $f;
        } else {
            return true;
        }
    }

    public static function getFolder($param)
    {
        if (is_dir($param)) {
            return $param;
        } else {
            $default = ArrayHelper::getValue(Yii::$app->params, 'fileUploadFolder', $param);
            $inParam = ArrayHelper::getValue(Yii::$app->params, $param, $default);
            return Yii::getAlias($inParam);
        }
    }

    public static function formPath($path, $folder = null)
    {
        $p = [$folder];
        for ($i = 0; $i < 3; $i++) {
            $p[] = $path[$i];
        }
        $p[] = $path;
        return join(DIRECTORY_SEPARATOR, $p);
    }

    public function getFullName()
    {
        return join('.', array_filter([$this->name, $this->extension]));
    }
}