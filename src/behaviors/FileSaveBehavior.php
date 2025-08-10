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
    public $saveFile;
    public $removeFileValue = 'remove';

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

        $data = [
            'file' => $this->_file,
            'field' => $this->field,
            'attribute' => $this->attribute,
            'fileClass' => $this->fileClass,
            'remove' => $value == $this->removeFileValue
        ];

        $this->owner->on($eventName, $this->saveFile instanceof \Closure ? $this->saveFile : [$this, 'saveFile'], $data);
    }

    public function saveFile($event)
    {
        $file = $event->data['file'];
        $field = $event->data['field'];
        $attribute = $event->data['attribute'];
        $fileClass = $event->data['fileClass'];
        $remove = $event->data['remove'];

        if ($file && $file instanceof UploadedFile) {
            $field = $field ?: $attribute . '_id';
            $this->owner->{$field} = $fileClass::startUpload($file)->process()->id;
        }
        
        if ($remove) {
            $this->owner->{$field} = null;
        }
    }
}