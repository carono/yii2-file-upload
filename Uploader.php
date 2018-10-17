<?php

namespace carono\yii2file;

use Yii;
use yii\base\Component;
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
class Uploader extends Component
{
    /**
     * @var FileUploadTrait
     */
    public $modelClass;
    public $data;
    public $file;
    public $name;
    public $slug;
    public $uid;
    public $delete = true;
    public $folder;
    public $context;
    protected $fileName;
    protected $filePath;

    /**
     * @param $name
     * @return $this
     */
    public function name($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function data($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function folder($path)
    {
        $this->folder = $path;
        return $this;
    }

    public function context($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @param bool $deleteOnFinish
     * @return $this
     */
    public function delete($deleteOnFinish = true)
    {
        $this->delete = $deleteOnFinish;
        return $this;
    }

    /**
     * @return null|string
     */
    protected function getSession()
    {
        if (isset(Yii::$app->session)) {
            $session = Yii::$app->session->getIsActive() ? Yii::$app->session->getId() : null;
        } else {
            $session = null;
        }
        return $session;
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->name ?: $this->fileName;
    }

    /**
     * @return string
     */
    protected function getFileNameWithOutExtension()
    {
        return basename($this->getFileName(), '.' . $this->getFileExtension());
    }

    /**
     * @return string
     */
    protected function getMimeType()
    {
        return FileHelper::getMimeType($this->filePath);
    }

    /**
     * @return mixed|string
     */
    protected function getFileExtension()
    {
        if (!$extension = strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION))) {
            $extension = ArrayHelper::getValue(FileHelper::getExtensionsByMimeType($this->getMimeType()), 0);
        }
        return $extension;
    }

    /**
     * @return int|null|string
     */
    public function getUserId()
    {
        if (isset(Yii::$app->user)) {
            $userId = Yii::$app->user->getId();
        } else {
            $userId = null;
        }
        return $userId;
    }

    /**
     * @param $source
     * @param $destination
     * @throws \Exception
     */
    protected function copyUploadedFile($source, $destination)
    {
        if (!move_uploaded_file($source, $destination)) {
            throw new \Exception('Unknown upload error');
        }
    }

    /**
     * @param $source
     * @param $destination
     * @throws \Exception
     */
    protected function renameFile($source, $destination)
    {
        if (!rename($source, $destination)) {
            throw new \Exception('Failed rename file');
        }
    }

    /**
     * @param $source
     * @param $destination
     * @throws \Exception
     */
    protected function copyFile($source, $destination)
    {
        if (!copy($source, $destination)) {
            throw new \Exception('Failed copy file');
        }
    }

    /**
     * @param $source
     * @param $destination
     * @throws \Exception
     */
    public function copy($source, $destination)
    {
        if (is_uploaded_file($source)) {
            $this->copyUploadedFile($source, $destination);
        } elseif ($this->delete) {
            $this->renameFile($source, $destination);
        } else {
            $this->copyFile($source, $destination);
        }
    }

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->uid = $this->uid ?: $this->formUid();
    }

    /**
     * @return string
     */
    public function getNewFileAlias()
    {
        return static::formAliasPath($this->getUid(), $this->folder);
    }

    /**
     * @param ActiveRecord $model
     * @param $attributes
     */
    public function loadAttributes($model, $attributes)
    {
        $model->setAttributes($attributes);
    }

    public function formAttributes()
    {
        $this->processFilePath($this->file);
        return [
            'session' => $this->getSession(),
            'name' => $this->getFileNameWithOutExtension(),
            'extension' => $this->getFileExtension(),
            'user_id' => $this->getUserId(),
            'uid' => $this->getUid(),
            'data' => !is_null($this->data) ? json_encode($this->data) : null,
            'mime_type' => $this->getMimeType(),
            'md5' => md5_file($this->filePath),
            'folder' => static::formAliasPath($this->getUid(), $this->folder),
            'slug' => $this->slug,
            'size' => filesize($this->filePath)
        ];
    }

    /**
     * @return FileUploadTrait|null
     * @throws \Exception
     */
    public function process()
    {
        /**
         * @var FileUploadTrait $model
         */
        $model = new $this->modelClass();
        $this->loadAttributes($model, $this->formAttributes());
        $newFilePath = $model->getRealFilePath();
        if (!is_dir($realFolder = dirname($newFilePath))) {
            if (!mkdir($realFolder, 0777, true) && !is_dir($realFolder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $realFolder));
            }
        }
        if (!file_exists($this->filePath)) {
            throw new \Exception('File not loaded or not exist');
        }
        $this->copy($this->filePath, $newFilePath);
        if ($model->save()) {
            return $model;
        }

        $model->deleteFile();
        return null;
    }

    /**
     * @param $file
     */
    protected function processFilePath($file)
    {
        if (strpos($file, 'http') === 0) {
            $context = $this->context ? stream_context_create($this->context) : null;
            $tmp = Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . uniqid('file_upload_', true);
            file_put_contents($tmp, file_get_contents($file, false, $context));
            $this->filePath = $tmp;
            $this->fileName = explode('?', basename($file))[0];
        } elseif (is_string($file)) {
            $this->filePath = Yii::getAlias($file);
            $this->fileName = basename($this->filePath);
        } elseif ($file instanceof UploadedFile) {
            $this->filePath = $file->tempName;
            $this->fileName = $file->name;
        }
    }

    /**
     * @param $slug
     * @return $this
     */
    public function slug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return string
     */
    protected function generateUid()
    {
        $sec = new Security();
        return md5($sec->generateRandomString(64));
    }

    /**
     * @return string
     */
    protected function formUid()
    {
        $class = $this->modelClass;
        do {
            $uPath = $this->generateUid();
        } while ($class::find()->where(['uid' => $uPath])->exists());
        return $uPath;
    }

    /**
     * @param $path
     * @param null|string $aliasPrefix
     * @return string
     */
    public static function formAliasPath($path, $aliasPrefix = null)
    {
        $p = $aliasPrefix ? [$aliasPrefix] : [];
        for ($i = 0; $i < 3; $i++) {
            $p[] = $path[$i];
        }
        return implode('/', $p);
    }
}