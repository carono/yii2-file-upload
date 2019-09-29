<?php


namespace carono\yii2file\behaviors;


use carono\yii2file\FileUploadTrait;
use carono\yii2migrate\traits\PivotTrait;
use yii\db\ActiveRecord;

/**
 * Class PivotSaveBehavior
 *
 * @package app\behaviors
 * @property PivotTrait|ActiveRecord $owner
 */
class PivotSaveBehavior extends \yii\base\Behavior
{
    protected $_pivots;
    public $attribute;
    public $field;
    public $pivotClass;
    public $fileClass;

    public function canSetProperty($name, $checkVars = true)
    {
        return $name == $this->attribute;
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
        if ($attribute == $this->attribute) {
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
        $class = $this->getModelClass();
        $this->_pivots = [];
        foreach ((array)$values as $value) {
            if (is_numeric($value)) {
                $this->_pivots[] = $class::findOne($value);
            } elseif ($value instanceof $class) {
                $this->_pivots[] = $value;
            }
        }
        $eventName = $this->owner->isNewRecord ? ActiveRecord::EVENT_AFTER_INSERT : ActiveRecord::EVENT_AFTER_UPDATE;
        $this->owner->on($eventName, [$this, 'savePivots']);
    }

    public function savePivots()
    {
        if ($this->_pivots !== null) {
            $this->owner->deletePivots($this->pivotClass);
        }
        if ($this->_pivots) {
            foreach ($this->_pivots as $pv) {
                $this->owner->addPivot($pv, $this->pivotClass);
            }
        }
    }
}