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
    public $pivotClass;
    public $modelClass;
    public $prepareValues;
    public $deletePivotsBeforeSave = true;

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
        return $this->modelClass;
    }

    protected function setPivots($values)
    {
        $class = $this->getModelClass();
        $this->_pivots = [];
        if ($this->prepareValues instanceof \Closure) {
            $values = call_user_func($this->prepareValues, (array)$values);
        }
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
        if (!method_exists($this->owner, 'addPivot')) {
            throw new \Exception('Class ' . get_class($this->owner) . ' must use carono\yii2migrate\traits\PivotTrait trait');
        }
        if ($this->deletePivotsBeforeSave && $this->_pivots !== null) {
            $this->owner->deletePivots($this->pivotClass);
        }
        if ($this->_pivots) {
            foreach ($this->_pivots as $pv) {
                $this->owner->addPivot($pv, $this->pivotClass);
            }
        }
    }
}