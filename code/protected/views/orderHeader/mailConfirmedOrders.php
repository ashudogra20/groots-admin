<?php

$w_id = $_GET['w_id'];
$this->breadcrumbs = array(
    'Orders' => array('admin&w_id='.$w_id),
);
?>
<?php if (Yii::app()->user->hasFlash('premission_info')): ?><div class="errorSummary"><?php echo Yii::app()->user->getFlash('premission_info'); ?></div><?php endif; ?>
<?php if(Yii::app()->user->hasFlash('success')):?>
    <div class="Csv" style="color:green;">
        <?php echo Yii::app()->user->getFlash('success'); ?>
        <?php echo Yii::app()->user->getFlash('prod'); ?>
    </div>
<?php endif; ?>
<?php if(Yii::app()->user->hasFlash('error')):?>
    <div class="Csv" style="color:red;">
        <?php echo Yii::app()->user->getFlash('error'); ?>
    </div>
<?php endif; ?>

<form name="myform" method="post" action="<?php echo Yii::app()->getBaseUrl().'/index.php?r=orderHeader/mailConfirmedOrders'    ;?>">
<?php
echo 'Select Date'."\t";
$this->widget('zii.widgets.jui.CJuiDatePicker', array(
    //'model' => $model,
    'name' => 'date',
    //'attribute' => 'deliverySummaryDeliveryDate',
    'flat' => false, //remove to hide the datepicker
    'options' => array(
        'showAnim' => 'slide', //'slide','fold','slideDown','fadeIn','blind','bounce','clip','drop'
        // 'minDate' => 0,
        'dateFormat' => 'yy-mm-dd',
        //'minDate'=>-5,
        'maxDate' => "+3M",
    ),
    'value' => $date,
    'htmlOptions' => array(
        'style' => 'float:center;',
    ),

));
echo '<br>'.'<br>'.'<br>';
echo ' Enter Text Subject'."\t".CHtml::textArea('Subject','', array('style' => 'float:center;'));
echo '<br>';


    $this->widget('zii.widgets.grid.CGridView', array(
        'id' => 'mail-pending-skus',
        //'model' => $model,
        'itemsCssClass' => 'table table-striped table-bordered table-hover',
        'dataProvider' => $model->searchMailShortOrders(),
        //'filter' => $model,
        'columns' => array(
            array(
                'header' => 'check',
                'name' => 'checkeId',
                'type' => 'raw',
                'value' => function($data){
                    echo CHtml::checkBox('checkedId_'.$data->base_product_id, false);
                }
            ),
            'base_product_id',
            'title',
            array(
                //'header' => 'Base Product Id',
                //'name' => 'base_product_id',
                //'type' => 'raw',
                'value' => function($data){
                    echo CHtml::hiddenField('base_product_id[]', $data->base_product_id);
                     //return $data->base_product_id;
                }
            ),
            array(
                //'header' => 'Title',
                //'name' => 'title',
                //'type' => 'raw',
                'value' => function($data){
                    echo CHtml::hiddenField('title[]', $data->title);
                    //return $data->title;
                }
            ),


        )
    ));
echo CHtml::button('Submit',array('submit' => array('orderHeader/mailConfirmedOrders&w_id='.$w_id), 'name'=>'sendMail'));
//echo '<br>'.'<br>';
?>
    <a href="index.php?r=orderHeader/admin" class="button_new" style="width: auto;"
       target="_self">Back</a>
</form>
