<?php

namespace carono\yii2file\behaviors;

use carono\yii2file\FileUploadTrait;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 * Class FileSaveBehavior
 *
 * @package app\behaviors
 * @property ActiveRecord $owner
 */
class FileSaveBehavior extends \yii\base\Behavior
{
    protected $_file;
    public $attribute;
    public $field;
    /**
     * @var FileUploadTrait
     */
    public $fileClass;

    public function canSetProperty($name, $checkVars = true)
    {
        return $name == $this->attribute;
    }

    public function __set($name, $value)
    {
        if ($this->canSetProperty($name)) {
            $this->setFile($value);
        } else {
            parent::__set($name, $value);
        }
    }

    protected function setFile($value)
    {
        if ($value instanceof UploadedFile) {
            $this->_file = $value;
        } else {
            $this->_file = UploadedFile::getInstance($this->owner, $this->attribute);
        }
        $eventName = $this->owner->isNewRecord ? ActiveRecord::EVENT_BEFORE_INSERT : ActiveRecord::EVENT_BEFORE_UPDATE;
        $this->owner->on($eventName, [$this, 'saveFile']);
    }

    public function saveFile()
    {
        if ($this->_file && $this->_file instanceof UploadedFile) {
            $field = $this->field ?: $this->attribute . '_id';
            $class = $this->fileClass;
            $this->owner->{$field} = $class::startUpload($this->_file)->process()->id;
        }
    }
}