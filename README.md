# mega_bahavior

Использование:

В методе behaviors прописываем следующий код

    public function behaviors()
        {
            return [
                'MegaBehavior' => [
                    'class' => MegaBehavior::className(),
                    'attach_class'   => [
                        UserInfo::className(),
                    ],
                ],
            ];
        }
    
 Параметр attach_class принимает класс или массив классов которые нужно прикрепить к модели. Данное поведение формирует массив 
 прикрепленных моделей, которые можно вызывать так: 
 
        $model->mega->UserInfo[index]
    
 Во вьюхах вызываем таким образом:
 
     $form->field($model->mega->UserInfo[0], 'surname')->textInput()

Если бутете находить косяки, то пишите в коментах, буду фиксить))))
