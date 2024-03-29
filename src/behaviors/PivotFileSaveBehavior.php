<?php


namespace carono\yii2file\behaviors;


use carono\yii2file\FileUploadTrait;
use carono\yii2migrate\traits\PivotTrait;
use Closure;
use yii\db\ActiveRecord;
use yii\web\UploadedFile;

/**
 * Class PivotFileSaveBehavior
 *
 * @package backend\behaviors
 * @property PivotTrait|ActiveRecord $owner
 */
class PivotFileSaveBehavior extends \yii\base\Behavior
{
    protected $_pivots;
    public $attribute;
    public $pivotClass;
    public $fileProcess;
    public $fileClass;
    public $pivotAttributes;

    public function canSetProperty($name, $checkVars = true)
    {
        return $name === $this->attribute;
    }

    public function __set($name, $value)
    {
        if ($this->canSetProperty($name)) {
            $this->setPivots($value);
        } else {
            parent::__set($name, $value);
        }
    }

    public function getStoredPivots($attribute)
    {
        if ($attribute === $this->attribute) {
            return $this->_pivots;
        }

        return null;
    }

    /**
     * @return ActiveRecord|FileUploadTrait
     */
    protected function getModelClass()
    {
        return $this->fileClass;
    }

    protected function setPivots($values)
    {
        if (empty(array_filter((array)$values))) {
            $values = UploadedFile::getInstances($this->owner, $this->attribute);
        }
        $class = $this->getModelClass();
        $this->_pivots = [];
        foreach ((array)$values as $value) {
            if (is_numeric($value)) {
                $this->_pivots[] = $class::findOne($value);
            } elseif ($this->fileProcess instanceof \Closure) {
                $value = call_user_func_array($this->fileProcess, [$value, $class]);
                if (is_array($value)) {
                    $this->_pivots = array_merge($this->_pivots, $value);
                } else {
                    $this->_pivots[] = $value;
                }
            } elseif ($value instanceof UploadedFile || is_string($value)) {
                $this->_pivots[] = $class::startUpload($value)->process();
            }
        }
        $eventName = $this->owner->isNewRecord ? ActiveRecord::EVENT_AFTER_INSERT : ActiveRecord::EVENT_AFTER_UPDATE;
        $this->owner->on($eventName, [$this, 'savePivots']);
    }

    public function savePivots()
    {
        foreach ((array)$this->_pivots as $index => $pv) {
            if ($this->pivotAttributes instanceof Closure) {
                $attributes = call_user_func_array($this->pivotAttributes, [$pv, $index]);
            } else {
                $attributes = $this->pivotAttributes ?: [];
            }
            $this->owner->addPivot($pv, $this->pivotClass, $attributes ?: []);
        }
    }
}