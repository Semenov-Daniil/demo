<?= $form->field($model, 'title', ['options' => ['class' => 'col-md-8 mb-3']])->textInput() ?>
        
                <?= $form->field($model, 'countModules', [
                    'options' => [
                        'class' => 'col-md-4 mb-3',
                    ],
                    'template' => '
                        {label}
                        <div class="input-step full-width">
                            <button type="button" class="minus material-shadow">–</button>
                            {input}
                            <button type="button" class="plus material-shadow">+</button>
                        </div>
                        {error}
                    ',
                ])->textInput(['type' => 'number', 'class' => 'cleave-number', 'min' => 1, 'value' => ($model->countModules ? $model->countModules : 1)]) ?>