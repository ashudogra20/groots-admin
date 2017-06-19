<?php
/* @var $this OrderHeaderController */
/* @var $model OrderHeader */
ob_clean();
//$issuperadmin = Yii::app()->session['is_super_admin'];
$w_id='';
if(isset($_GET['w_id'])){
    $w_id = $_GET['w_id'];
}
$this->menu=array(
    //array('label'=>'List InventoryHeader', 'url'=>array('index')),
    array('label'=>'Create Order', 'url'=>array('create&w_id='.$w_id)),
    array('label' => 'Mail Short Sku', 'url' => array('mailConfirmedOrders&w_id='.$w_id))
);
$isAdmin = false;
if($this->checkAccess('SuperAdmin')){
    $isAdmin = true;
}
/*
if ($issuperadmin == 0) {

    if (empty(Yii::app()->session['brand_admin_id']) && empty(Yii::app()->session['brand_id'])) {

        Yii::app()->user->setFlash('permission_error', 'You are doing something wrong.');
        $this->redirect(array('DashboardPage/index'));
    }
    $store_id = Yii::app()->session['brand_admin_id'];
    if (empty($store_id)) {
        $store_id = Yii::app()->session['brand_id'];
    }
    //$store_id = $_GET['store_id'];
    $store_name = Store::model()->getstore_nameByid($store_id);

    if ((Yii::app()->session['brand_admin_id'] != $store_id) && (Yii::app()->session['brand_id'] != $store_id)) {
        //echo Yii::app()->session['brand_admin_id'];die;
        Yii::app()->user->setFlash('permission_error', 'You are doing something wrong.');
        $this->redirect(array('DashboardPage/index'));
    }

    $this->breadcrumbs = array(
        'Brand' => array('store/admin'),
        $store_name => array('store/update', "id" => $store_id),
        'Orders' => array('orderHeader/admin&w_id='.$w_id,),
        'Manage',
    );



}*/

    $this->breadcrumbs = array(
        'Orders' => array('admin&w_id='.$w_id,),
        'Manage',
    );

?>
<script>
    function confirmation_function() {
        confirm("Do you want to cancel");
    }
</script>
<!--particular delivery date download csv file-->
<!--<div class="particular_delivery_date_csv" style="align:right"; >

    <?php
/*     $this->widget('zii.widgets.jui.CJuiDatePicker',array(
                    'name'=>'particular_delivery_date_csv_from',
                    'id'=>'particular_delivery_date_csv_from',
                     'options'=>array(
                                 'dateFormat' => 'dd-mm-yy',
                                
                                'showAnim'=>'fold',
                                ),
                        'htmlOptions'=>array(
                        'style'=>'height:20px;'
                                ),
                        )); 
                    */?>
                
                <label>To</label>
               <?php
/*                $this->widget('zii.widgets.jui.CJuiDatePicker',array(
                        'name'=>'particular_delivery_date_csv_to',
                        'id'=>'particular_delivery_date_csv_to',
                        'options'=>array(
                                 'dateFormat' => 'dd-mm-yy',
                                //'maxDate' => "+3M",
                                //'showAnim'=>'fold',
                                
                                'minDate'=>'#particular_delivery_date_csv_from',

                                ),
                        'htmlOptions'=>array(
                        'style'=>'height:20px;'
                                ),
                        )); 
                */?>
                <input name="filter" class="button_new" type="submit" value="Filter" />
</div>-->
<div class="search-form" style="display:none">

    <?php
    $this->renderPartial('_search', array(
        'model' => $model,
    ));
    ?>
</div><!-- search-form -->
<form name="myform" method="post" action="<?php echo Yii::app()->getBaseUrl().'/index.php?r=orderHeader/admin&w_id='.$w_id;?>">
<!--    --><?php //echo CHtml::button('Mail Short Orders' , array('name' => 'mailConfirmedOrders','style' =>'float:left;', 'class' => 'button_new', 'submit' => array('orderHeader/mailConfirmedOrders')));?>

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
<!--<input name="cancelbutton" class="activebutton" value="Cancel Order" type="submit" 
onclick='return confirm("Do you want to cancel");'/>
<input  type="submit" name="sandbutton" class="activebutton" value="send CSV File" />
<input  type="submit" name="downloadbutton" class="activebutton" value="Download CSV File" />-->
    <!--<input  type="submit" name="status" class="activebutton"  value="Change status" />-->

    <?php
//$pageSize = Yii::app()->user->getState( 'pageSize', Yii::app()->params[ 'defaultPageSize' ] );
    $pageSize = 10;
    $pageSizeDropDown = CHtml::dropDownList(
                    'pageSize', $pageSize, array(10 => 10, 25 => 25, 50 => 50, 100 => 100,500 => 500), array(
                'class' => 'change-pagesize',
                'onchange' => "$.fn.yiiGridView.update('order-header-grid',{data:{pageSize:$(this).val()}});",
                    )
    );
    ?>
    <input name="status" class="activebutton" value="submit" type="submit">

    <div class="dropdownCustom">
        <!--        <select onchange="changestatus(this);">-->
        <select name="reportDownload" value="reportDownload">
            <option value="">Select to Download</option>
            <option value="invoice">Invoice</option>
            <option value="dc">Delivery Challan</option>
            <option value="email-invoice">Send Email Invoice</option>
        </select>
    </div>

    <div class="dropdownCustom">
<!--        <select onchange="changestatus(this);">-->
        <select name="status1" value="changestatus">
            <option>Change status</option>
            <option value="pending">Pending</option>
            <option value="Confirmed">Confirmed</option>
            <option value="Out for Delivery">Out for Delivery</option>
            <option value="Delivered">Delivered</option>
            <option value="Cancelled">Cancelled</option>
            <option value="Paid">Paid</option>
        </select>
    </div>
    <style>
        
        .page-size-wrap{float:left;}
        .dropdownCustom {
            float: right;
        }
    </style>


    <?php
    Yii::app()->clientScript->registerCss('initPageSizeCSS', '.page-size-wrap{text-align: left;}');

    $this->widget('zii.widgets.grid.CGridView', array(
        'itemsCssClass' => 'table table-striped table-bordered table-hover',
        'id' => 'order-header-grid',
        'dataProvider' => $model->search(),
        'filter' => $model,
        //  'summaryText'  => '{Start} - {End} / {count}',
        'columns' => array(
            array(
                'header' => 'check',
                'name' => 'selectedIds[]',
                'id' => 'selectedIds',
                'value' => '$data->order_id',
                'class' => 'CCheckBoxColumn',
                'selectableRows' => '100',
            ),
            array(
                'header' => 'S.N.',
                'type' => 'raw',
                'value' => '$this->grid->dataProvider->pagination->currentPage * $this->grid->dataProvider->pagination->pageSize + ($row+1)',
            ),
            'order_number',   
            array(
                'header' => 'Billing name',
                'name' => 'name',
                'type' => 'raw',
                'value'=> function($data){
                    return $data->name;
                }
            ),
                 array(
                'header' => 'Address',
                'name' => 'shipping_address',
                'type' => 'raw',
                'value'=> function($data){
           return $data->address.', '.$data->city.', '.$data->state;
       }
       ), 

            array(
                 'header' => 'Demand Centre',
                'name' => 'shipping_state',
                'type' => 'raw',
                'value'=> function($data){
           return ucfirst($data->warehouse_name);
       }
       ),
           /* array(
                'header' => 'Order status',
                'name' => 'status',
                'type' => 'raw',
            ),*/
               
                array(
                'header' => 'Order status',
                'name' => 'status',
                 'filter'=>array(''=>'All','pending'=>'Pending','Confirmed'=>'Confirmed','Out for Delivery'=>'Out for Delivery','Delivered'=>'Delivered','Cancelled'=>'Cancelled','Paid'=>'Paid'), 
               // 'value' => 'CHtml::button("View",array("onclick"=>"document.location.href=\'".Yii::app()->controller->createUrl("OrderHeader/update",array("status"=>$data->status))."\'"))',
                 ),
//            array(
//                'header' => 'Amount',
//                'name' => 'total',
//                'type' => 'raw',
//             'value' => '$this->grid->dataProvider->pagination->currentPage * $this->grid->dataProvider->pagination->pageSize + ($row+1)',
//            ),
           // 'created_date',
                     array(
                   'name' => 'created_date',
                'type' => 'raw',
                'value'=> function($data){
          
           return $data->created_date;
       }
       ),
           
            //'delivery_date',
                 array(
                   'name' => 'delivery_date',
                'type' => 'raw',
                'value'=> function($data){
           return trim($data->delivery_date);
       }
       ),
            'link' => array(
                'header' => 'Action',
                 'headerHtmlOptions' => array('style' => 'color:#1d2e7b;'),
                'type' => 'raw',
                'value' => function($data) use ($w_id, $isAdmin) {
                    $text = "Update";
                    /*if(($data->delivery_date < date('Y-m-d') || $data->status=='Delivered') && !$isAdmin){
                        $text = "View";
                    }*/
                     return CHtml::button($text,array("onclick"=>"document.location.href='".Yii::app()->controller->createUrl("OrderHeader/update",array("id"=>$data->order_id,"w_id"=>$w_id, "bckstatus"=>$data->status))."'"));
                },
            ),
               
            'link1' => array(
                'header' => 'Invoice',
                 'headerHtmlOptions' => array('style' => 'color:#1d2e7b;'),
                'type' => 'raw',
                'htmlOptions' => array('class' => 'redview'),
               // 'value' => 'CHtml::button(" CREATE INVOICE",array("onclick"=>"document.location.href=\'".Yii::app()->controller->createUrl("OrderHeader/report",array("id"=>$data->order_id))."\'"),array("target"=>"_blank"))',
             'value'=> function($data) use ($w_id) {
                 return CHtml::link("INVOICE", array("OrderHeader/report", "id"=>$data->order_id, "w_id"=>$w_id, "type"=>"invoice"),array("target"=>"_blank"));
             },
                ),
            'link2' => array(
                'header' => 'Delivery Challan',
                'headerHtmlOptions' => array('style' => 'color:#1d2e7b;'),
                'type' => 'raw',
                'htmlOptions' => array('class' => 'redview'),
                // 'value' => 'CHtml::button(" CREATE INVOICE",array("onclick"=>"document.location.href=\'".Yii::app()->controller->createUrl("OrderHeader/report",array("id"=>$data->order_id))."\'"),array("target"=>"_blank"))',
                'value'=> function($data) use ($w_id) {
                    return CHtml::link("DC", array("OrderHeader/report", "id"=>$data->order_id, "w_id"=>$w_id, "type"=>"dc"),array("target"=>"_blank"));
                },
            ),
        /* 'link1' => array(
          'header' => 'Dispatch',
          'type' => 'raw',
          'value' => 'CHtml::button("Dispatch",array("onclick"=>"document.location.href=\'".Yii::app()->controller->createUrl("OrderHeader/dispatch",array("id"=>$data->order_id))."\'"))',
          ) */
        ),
    ));
    ?>
</form>
<script type="text/javascript">
    function changestatus (ve)
    {
       // alert( ve.value );
       
       
    }
    </script>
    
   