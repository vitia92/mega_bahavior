<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 14.12.15
 * Time: 15:48
 */

namespace common\components;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;

class Mega extends ActiveRecord
{
    private $array;
    public function __get($name)
    {
        if(isset($this->array[$name]))
            return $this->array[$name];
        else return parent::__get($name);
    }
    public function __set($name,$value)
    {
        if(is_array($value)) $this->array[$name] = $value;
        else $this->array[$name] = [$value];
    }
}

class MegaBehavior extends Behavior
{
    public $attach_class;
    protected $models = [];
    protected $class_name = [];
    public $mega;

    private $delete_models;
    private $not_deleted_models = [];

    public function attach($owner)
    {
        parent::attach($owner);
        if(is_array($this->attach_class)){
            foreach($this->attach_class as $one_class){
                array_push($this->class_name, $one_class);
            }
        }else array_push($this->class_name,$this->class_name);
        $this->mega = new Mega();
        $this->afterFind();
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind'
        ];
    }

    protected function createArray($class)
    {
        $model = \Yii::createObject($class);
        $behavior_model_item = $model::find()->where(['entity_id' => $this->owner->id])->all();
        $bace_class_name = \yii\helpers\StringHelper::basename($class);
        if($behavior_model_item){
            $this->mega->{$bace_class_name} = $behavior_model_item;
        }else{
            $this->mega->{$bace_class_name} = $model;
        }
    }

    public function afterFind()
    {
        if($this->class_name){
            foreach($this->class_name as $one_class){
                $this->createArray($one_class);
            }
        }
    }

    public function afterUpdate(){
        $this->_save_changes();
    }

    public function afterInsert(){
        $this->_save_changes();
    }

    public function afterDelete(){
        if($this->class_name){
            foreach($this->class_name as $one_class){
                $model = \Yii::createObject($one_class);
                $this->delete_models = $model::find()->where(['entity_id' => $this->owner->id])->all();
                $this->delete_all_models();
            }
        }
    }

    public function delete_all_models()
    {
        if($this->delete_models){
            foreach($this->delete_models as $model){
                $model->delete();
            }
        }
    }

    protected function _save_changes(){
        if($this->models){
            foreach($this->models as $model){
                $bace_class_name = \yii\helpers\StringHelper::basename($model::className());
                $model->entity_id = $this->owner->id;
                $model->save();
                $this->not_deleted_models[$bace_class_name][] = $model->id;
            }
            foreach($this->attach_class as $one_el){
                $bace_class_name = \yii\helpers\StringHelper::basename($one_el);
                $model = \Yii::createObject($one_el);
                if(isset($this->not_deleted_models[$bace_class_name]) && $this->not_deleted_models[$bace_class_name]){
                    $model::deleteAll('entity_id='.$this->owner->id.' AND NOT id  IN ('.implode(",",$this->not_deleted_models[$bace_class_name]).")");
                }
            }

        }
    }

    public function beforeValidate(){
        $this->models = [];
        if($this->class_name){
            foreach($this->class_name as $class_name){
                $bace_class_name = \yii\helpers\StringHelper::basename($class_name);
                if($models = \Yii::$app->request->post($bace_class_name, false)){
                    if(isset($models[0])){
                        foreach($models as $k => $val){
                            $this->AddModelToArray($class_name,$val);
                        }
                    }else{
                        $this->AddModelToArray($class_name,$models);
                    }
                }
            }
        }
    }

    protected function AddModelToArray($class_name,$val)
    {
        if(isset($val['id'])){
            $model = $class_name::find()->where(['id'=>$val['id']])->one();
            if(!$model) $model = \Yii::createObject($class_name);
        }else $model = \Yii::createObject($class_name);
        $model->attributes = $val;
        if($model->validate()) $this->models[] = $model;
    }


}