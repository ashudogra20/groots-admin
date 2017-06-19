<?php

class OrderHeaderController extends Controller {

    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/column2';

    /**
     * @return array action filters
     */
    public function filters() {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete', // we only allow deletion via POST request
            );
    }
    /*
    protected function beforeAction($action) {
        $session = Yii::app()->session['user_id'];
        if ($session == '') {
            echo Yii::app()->controller->redirect("index.php?r=site/logout");
        }
        if (Yii::app()->session['premission_info']['menu_info']['brand_menu_info'] != "S") {
            Yii::app()->user->setFlash('permission_error', 'You have no permission');
            Yii::app()->controller->redirect("index.php?r=DashboardPage/index");
        }

        return true;
    }*/
    protected function beforeAction() {
        //var_dump($this->action->id);die;
        $w_id='';
        if($this->action->id == 'sendMailToRetailerWithOrderId'){
            return true;
        }
        if($this->action->id == 'mailConfirmedOrders'){
            $w_id = $_GET['w_id'];
            if($this->checkAccessByData('WarehouseEditor', array('warehouse_id'=>$w_id))){
                return true;    
            }
        }
        else{
            if(parent::beforeAction()){
                if(isset($_GET['w_id'])){
                    $w_id = $_GET['w_id'];
                }
                if($w_id>0 && $this->checkAccessByData('OrderViewer', array('warehouse_id'=>$w_id))){
                    return true;
                }
                elseif($this->checkAccess('SuperAdmin')){
                    return true;
                }
                else{
                    Yii::app()->user->setFlash('permission_error', 'You have no permission to access this page');
                    Yii::app()->controller->redirect("index.php?r=user/profile");
                }
            }    
        }
        
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array(
            array('allow', // allow all users to perform 'index' and 'view' actions
                'actions' => array('index', 'sales_by_retailer', 'sales_by_retailer_detail', 'sale_by_style', 'sale_by_style_dateial', 'sale_summery', 'sale_summery_detail', 'view', 'Reportnew', 'admin', 'report', 'Dispatch', 'productPricesByRetailerAndDate', 'sendMailToRetailerWithOrderId'),
                'users' => array('*'),
                ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('create', 'update', 'admin', 'report', 'Reportnew', 'Dispatch','sendMailToRetailerWithOrderId', 'mailConfirmedOrders'),
                'users' => array('@'),
                ),
            array('allow', // allow admin user to perform 'admin' and 'delete' actions
                'actions' => array('admin', 'delete', 'admin', 'Reportnew', 'report','sendMailToRetailerWithOrderId'),
                'users' => array('admin'),
                ),
             array('deny', // deny all users
                 'users' => array('*'),
                 ),
            );
    }

    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id) {
        $this->render('view', array(
            'model' => $this->loadModel($id),
            ));
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     */
    public function actionCreate()
    {
        //print("<pre>");
        //var_dump($_POST);die;
        $w_id = '';
        if(isset($_GET['w_id'])){
            $w_id = $_GET['w_id'];
        }
        if(!$this->checkAccessByData('OrderEditor', array('warehouse_id'=>$w_id))){
            Yii::app()->user->setFlash('premission_info', 'You dont have permission.');
            Yii::app()->controller->redirect("index.php?r=orderHeader/admin&w_id=".$w_id);
        }
        $model = new OrderHeader;
        $retailerProducts = '';
        $retailerId = '';
        $retailer = '';
        $warehouses = '';
        //print_r($_POST);
        //print_r($_GET);
        //die;

        if (isset($_POST['select-retailer'])) {

            $retailerId = $_POST['retailer-dd'];
            if($retailerId > 0 && self::checkCreditLimit($retailerId)) {
                $retailerProducts = RetailerproductquotationGridview::model()->findAllByAttributes(array('retailer_id' => $retailerId), array('order'=> 'title ASC'));
                $retailer = Retailer::model()->findByPk($retailerId);
                $warehouses = Warehouse::model()->findAll();
                $model->expected_delivery_time = $retailer->delivery_time;
            }
            elseif(!self::checkCreditLimit($retailerId)){
                Yii::app()->user->setFlash('error', 'Credit Limit Exceeded.');
            }

        }
        if (isset($_POST['create'])) {
            echo "here3";
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $retailerId = $_POST['retailerId'];
                $retailerProducts = RetailerproductquotationGridview::model()->findAllByAttributes(array('retailer_id' => $retailerId), array('order'=> 'title ASC'));
                $retailer = Retailer::model()->findByPk($retailerId);
                $warehouses = Warehouse::model()->findAll();
                $orderHeader = new OrderHeader();
                $orderHeader->user_id = $retailerId;
                $orderHeader->status = $_POST['status'];
                if(isset($_POST['comment']) && !empty($_POST['comment'])){
                    $orderHeader->user_comment = $_POST['comment'];
                }
                if(isset($_POST['shippingCharge'])){
                    $orderHeader->shipping_charges = $_POST['shippingCharge'];
                }
                if(isset($_POST['discountCharge']) && !empty($_POST['discountCharge'])){
                    $orderHeader->discount_amt = $_POST['discountCharge'];
                }
                $orderHeader->total=$_POST['sumAmount'];
                $orderHeader->total_payable_amount =$_POST['finalAmount'];
                $orderHeader->delivery_date = $_POST['deliveryDate'];
                $orderHeader->warehouse_id = $_POST['warehouse'];
                $orderHeader->billing_email = $retailer->email;
                $orderHeader->order_number = $this->getNextOrderNo();
                $orderHeader->created_date = date("y-m-d H:i:s");
                //var_dump($retailer->delivery_time);die;
                $orderHeader->expected_delivery_time = $retailer->delivery_time;
                $orderHeader->actual_delivery_time = '00:00:00';
                $orderHeader->updated_by = Yii::app()->user->id;
                $orderHeader->discount_amt = $_POST['discountAmount'];
                //var_dump($orderHeader);die;
                $orderHeader->save();
                foreach ($_POST['quantity'] as $key => $quantity) {
                    if ($quantity > 0) {
                        $orderLine = new OrderLine();
                        $orderLine->order_id = $orderHeader->order_id;
                        $orderLine->subscribed_product_id = $_POST['subscribed_product_id'][$key];
                        $orderLine->base_product_id = $_POST['base_product_id'][$key];
                        $orderLine->product_qty = $quantity;
                        $orderLine->delivered_qty = $quantity;
                        $orderLine->unit_price = $_POST['store_offer_price'][$key];
                        $orderLine->price = $_POST['amount'][$key];
                        $orderLine->store_id = 1;
                        $orderLine->created_date = date("y-m-d H:i:s");
                        $orderLine->save();
                    }
                }
                Yii::app()->user->setFlash('success', 'Order Created Successfully.');
                if($orderHeader->status == 'Delivered')
                    $retailer->total_payable_amount += $orderHeader->total_payable_amount;
                    $retailer->updated_by = Yii::app()->user->id;
                $retailer->save();
                $transaction->commit();
                $this->redirect(array('OrderHeader/admin&w_id='.$w_id));
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::app()->user->setFlash('error', 'Order Creation failed.');
                throw $e;
            }
        }
//var_dump($retailer);die;
        $this->render('create', array(
            'model' => $model,
            'retailerProducts' => $retailerProducts,
            'retailer' => $retailer,
            'retailerId' => $retailerId,
            'warehouses' => $warehouses,
            ));

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        /*if (isset($_POST['OrderHeader'])) {
            $model->attributes = $_POST['OrderHeader'];
            if ($model->save())
                $this->redirect(array('view', 'id' => $model->order_id));
            }*/


        }

        private function getNextOrderNo(){
            $lastOrderId = OrderHeader::getLastOrderId();
            $currentOrderId = $lastOrderId+1;
            return ORDER_NUMBER_PREFIX.$currentOrderId;
        }

        private function initailize($retailerId){
            global $retailerProducts,$retailer,$warehouses;
            $retailerProducts = RetailerproductquotationGridview::model()->findAllByAttributes(array('retailer_id' => $retailerId));
            $retailer = Retailer::model()->findByPk($retailerId);
            $warehouses = Warehouse::model()->findAll();

        }


        public  function  actionUpdate($id){
//print("<pre>");
//        print_r($_POST);die;
            //var_dump($_POST);die;
            $w_id = '';
            if(isset($_GET['w_id'])){
                $w_id = $_GET['w_id'];
            }
            if(!$this->checkAccessByData('OrderEditor', array('warehouse_id'=>$w_id))){
                Yii::app()->user->setFlash('premission_info', 'You dont have permission.');
                Yii::app()->controller->redirect("index.php?r=orderHeader/admin&w_id=".$w_id);
            }
            $retailerProducts = '';
            $retailerId = '';
            $retailer = '';
            $warehouses = '';
            $itemArray = '';
            $orderLine = OrderLine::model()->findAllByAttributes(array('order_id' => $id));
            $orderHeader = $this->loadModel($id);
            $orderAmount = $orderHeader->total_payable_amount;
            $initialStatus = $orderHeader->status;
            $initialShippingCharge = $orderHeader->shipping_charges;
            $retailerId = $orderHeader->user_id;
            $baseProductIds = array();
            $baseProductIdPriceMap = array();
            $retailerProducts = RetailerproductquotationGridview::model()->findAllByAttributes(array('retailer_id' => $retailerId), array('order'=> 'title ASC'));
            foreach ($retailerProducts as $product){
                array_push($baseProductIds, $product->base_product_id);
            }

            $productPrices = ProductPrice::model()->findAllByAttributes(array('base_product_id'=>$baseProductIds, 'effective_date'=>$orderHeader->delivery_date),array('select'=>'base_product_id, store_price, store_offer_price'));

            if(empty($productPrices)){
                Yii::app()->user->setFlash('error', 'Prices of the order delivery date are not available.');
                $this->redirect(Yii::app()->request->urlReferrer);
            }
            foreach ($productPrices as $productPrice){
                $baseProductIdPriceMap[$productPrice->base_product_id] = $productPrice;
            }
            $dateOnly = new DateTime($orderHeader->delivery_date);
            $dateOnly = $dateOnly->format('Y-m-d');
            $resultEffectivePrice = self::getEffectivePrice($retailerId, $dateOnly);
            foreach ($retailerProducts as $key=>$product){
                if(isset($resultEffectivePrice[$product->base_product_id]) && $resultEffectivePrice[$product->base_product_id]>0){
                    $product->effective_price = $resultEffectivePrice[$product->base_product_id];
                }
                if(isset($baseProductIdPriceMap[$product->base_product_id]))  {
                    $product->store_price = $baseProductIdPriceMap[$product->base_product_id]->store_price;
                    $product->store_offer_price = $baseProductIdPriceMap[$product->base_product_id]->store_offer_price;
                }
                else{
                    $product->store_price = 0;
                    $product->store_offer_price = 0;
                }
                $retailerProducts[$key] = $product;
            }

            $retailer = Retailer::model()->findByPk($retailerId);
            $warehouses = Warehouse::model()->findAll();

            foreach ($orderLine as $item){
                $itemArray[$item->base_product_id] = $item;
            }

            if (isset($_POST['update'])) {
            //print("<pre>");
            //print_r($_POST);die;
            //print_r($_POST);die;
            //print_r($_POST);die;
                $transaction = Yii::app()->db->beginTransaction();
                try {

                    $orderHeader->status = $_POST['status'];
                    if (isset($_POST['comment']) && !empty($_POST['comment'])) {
                        $orderHeader->user_comment = $_POST['comment'];
                    }
                    if (isset($_POST['shippingCharge'])) {
                        $orderHeader->shipping_charges = $_POST['shippingCharge'];
                    }
                    if (isset($_POST['discountCharge']) && !empty($_POST['discountCharge'])) {
                        $orderHeader->discount_amt = $_POST['discountCharge'];
                    }
                    $orderHeader->total = $_POST['sumAmount'];
                    $orderHeader->total_payable_amount = $_POST['finalAmount'];
                    $orderHeader->delivery_date = $_POST['deliveryDate'];
                    $orderHeader->warehouse_id = $_POST['warehouse'];
                    if(isset($_POST['OrderHeader']['actual_delivery_time']) && !empty($_POST['OrderHeader']['actual_delivery_time'])){
                        $orderHeader->actual_delivery_time = $_POST['OrderHeader']['actual_delivery_time'];
                    }
                    if(isset($_POST['OrderHeader']['expected_delivery_time']) && !empty($_POST['OrderHeader']['expected_delivery_time'])){
                        $orderHeader->expected_delivery_time = $_POST['OrderHeader']['expected_delivery_time'];
                    }
                    $orderHeader->updated_by = Yii::app()->user->id;
                    $orderHeader->discount_amt = $_POST['discountAmount'];
                    $orderHeader->save();
                    foreach ($_POST['quantity'] as $key => $quantity) {

                        $orderQt = $_POST['product_qty'][$key];
                        if ($quantity > 0 || $orderQt > 0) {
                            if(isset($itemArray[$_POST['base_product_id'][$key]])){
                                $orderLine = $itemArray[$_POST['base_product_id'][$key]];
                            }
                            else{
                                $orderLine = new OrderLine();
                                $orderLine->order_id = $orderHeader->order_id;
                                $orderLine->subscribed_product_id = $_POST['subscribed_product_id'][$key];
                                $orderLine->base_product_id = $_POST['base_product_id'][$key];
                                $orderLine->created_date = date("y-m-d H:i:s");
                            }


                            $orderLine->product_qty = $orderQt;
                            $orderLine->delivered_qty = $quantity;
                            $orderLine->unit_price = $_POST['store_offer_price'][$key];
                            $orderLine->price = $_POST['amount'][$key];
                            $orderLine->store_id = 1;
                            $orderLine->unsetAttributes(array('updated_at'));
                            $orderLine->save();
                        }
                        else{
                            if(isset($itemArray[$_POST['base_product_id'][$key]])){
                                $orderLine = $itemArray[$_POST['base_product_id'][$key]];
                                $orderLine->deleteByPk($orderLine->id);
                            }

                        }
                        if($key ==3){
                        //print_r($orderLine);die;
                        }

                    }
                    Yii::app()->user->setFlash('success', 'Order Updated Successfully.');
                    if($initialStatus != 'Delivered' && $orderHeader->status == 'Delivered'){
                        $retailer->total_payable_amount += $orderHeader->total_payable_amount;
                    }
                    if($initialStatus== 'Delivered' && $orderHeader->status != 'Delivered')
                        $retailer->total_payable_amount -= $orderAmount;
                    if($initialStatus == $orderHeader->status){
                        $retailer->total_payable_amount -= $orderAmount;
                        $retailer->total_payable_amount += $orderHeader->total_payable_amount;
                    }
                    if($_POST['status'] == 'Delivered'){
                        OrderHeader::setFeedbackStatusOnDelivered($id, 'Pending');
                    }
                    else{
                        OrderHeader::setFeedbackStatusOnDelivered($id, 'Not Required');
                    }
                    $retailer->updated_by = Yii::app()->user->id;
                    $retailer->save();
                    $transaction->commit();
                //$this->redirect(array('OrderHeader/admin&w_id='.$w_id));
                    $url = Yii::app()->controller->createUrl("OrderHeader/admin",array("w_id"=>$w_id));
                //$url = Yii::app()->controller->createUrl("OrderHeader/update",array("w_id"=>$w_id, "id"=>$orderHeader->order_id));
                    Yii::app()->user->setFlash('success', 'order successfully Updated.');
                    Yii::app()->controller->redirect($url);
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Yii::app()->user->setFlash('error', 'Order Update failed.');
                    throw $e;
                }

            }


            $this->render('updateNew', array(
                'model' => $orderHeader,
                'orderLine' => $itemArray,
                'retailerProducts' => $retailerProducts,
                'retailer' => $retailer,
                'retailerId' => $retailerId,
                'warehouses' => $warehouses,
                'initialShippingCharge' => $initialShippingCharge,
                ));


        }
    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdateOld($id) {
        $model = OrderLine::model()->findAllByAttributes(array('order_id' => $id));
        $modelOrder = $this->loadModel($id);

        $email = $modelOrder->attributes['billing_email'];

        $shipping_charge=0;

        if (isset($_POST['Update'])) {
         //print_r($_POST);die;
         if(!empty($_POST['shipping_charges'])){
          $shipping_charge=$_POST['shipping_charges'];
      }
      $totalchk = 0;
      $unit_price_discount = array();
            //....................end..........................//
      $linedescinfo = new OrderLine;
      $linedescinforeport = new OrderHeader;
      $status = array();
      if (isset($_POST['Status']['0'])) {
        $status = $_POST['Status'];
        $no_Status = count($_POST['Status']['0']);
        $update_status = $_POST['Status']['0'];
        $status_data[0] = $_POST['Status']['0'];
        $lineinfo = $linedescinfo->UpdatedStatus($update_status, $_REQUEST['order_id']);

                //echo $reportdata;die;
    }

    if ($status_data[0] == 'Confirmed') {
                //$reportdata = $this->actionReportnew($_REQUEST['order_id'], $status_data[0], $email);
        $modelOrderline = new OrderLine;
        $buyername = $modelOrderline->buyername($modelOrder->attributes['user_id']);
        $from_email = 'grootsadmin@groots.in';
        $from_name = 'Groots Dashboard Admin';
        $subject = 'Groots Buyer Account';
        $urldata = Yii::app()->params['target_app_url'];
        $emailurldata = Yii::app()->params['email_app_url1'];
//                $body_html = 'Hi  <br/> your order id ' . $modelOrder->attributes['order_id'] . ' <br/> status now change<br/><br/><br> ,
//                                            <br/>' . $status_data[0] . ' <br/>';
        $body_html = '<html xmlns="http://www.w3.org/1999/xhtml">
        <head>
          <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
          <title>Email Verification </title>
          <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
          <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:400,600,300italic,700,700italic" rel="stylesheet" type="text/css">
      </head>
      <body style="margin: 0; padding: 0; font-family: sans-serif;">
          <table align="center"  cellpadding="0" border="0" cellspacing="0" width="600" style="border-collapse: collapse; display: block; border:0; background:#fff; ">
            <tbody>
                <tr style="display: block; ">
                  <td style="padding:0px; width: 150px; background-color: #444;" >
                    <a href="javascript:void(0);" style="display:block; height:63px; "><img src="' . $emailurldata . 'emailimage/logo.png" alt="" style="    width: 50px;
                        margin: 8px 20px;"></a>
                    </td>
                    <td style="padding: 5px 10px; width:450px; background-color:#444;color: #fff;font-size: 24px; text-transform: uppercase; text-align:right;">
                        <span style="float:right;">'. CUSTOMER_SUPPORT_NO. '</span>
                        <img src="' . $emailurldata . 'emailimage/callIco-head.png" alt="call" width="25" style="float:right; margin:0 10px;"> 
                    </td>
                </tr>
                <tr>
                  <td colspan="2" style="text-align:center;padding:20px 0; background:#fff url(' . $emailurldata . 'emailimage/bg-repeat.jpg) repeat-x;">
                    <img src="' . $emailurldata . 'emailimage/check-shadow.png" alt="call" width="100" style=" margin:20px auto;"> 
                </td>
            </tr>
            <tr style="display: block;">
              <td colspan="2" style="display: block; padding: 10px;border: 1px solid #f7f7f7;border-width: 1px 2px 0;">
                <p style="font-size:20px;">
                  <strong>Hi ' . $buyername . '</strong>
                  <br> 
                  <span style="margin-top:15px; display:block; font-size:14px; line-height:30px;">
                    Your order (' . $modelOrder->attributes['order_number'] . ') is now   ' . $status_data[0] . '. We will keep you posted for further updates.<br>
                    Thank you for choosing Groots!

                </span>
                <br>

                <a href="' . $urldata . '">
                 <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
             </a>
             <br>
             <span style="font-size:14px;">Ordering: '. ORDER_SUPPORT_NO. '<br>
                Customer Support: '. CUSTOMER_SUPPORT_NO. '<br>
                Sales: '. SALES_SUPPORT_NO. '</span>
                <br> <br> 
            </p>
        </td>          
    </tr>
    <tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
      <td colspan="2" style="width: 600px;">
        <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
          <li style="display:block; width:170px; float:left; text-align:center;">
            <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
        </li>
        <li style="display:block; width:170px; float:left; text-align:center;">
            <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
        </li>
        <li style="display:block; width:170px; float:left; text-align:center;">
            <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
        </li>
    </ul>
</td> 
</tr>
</tbody></table>
</body>
</html>';

$body_text = '';

$mailArray = array(
    'to' => array(
        '0' => array(
            'email' => "$email",
            )
        ),
    'from' => $from_email,
    'fromname' => $from_name,
    'subject' => $subject,
    'html' => $body_html,
    'text' => $body_text,
    'replyto' => $from_email,
    );
$mailsend = new OrderLine();
$resp = $mailsend->sgSendMail($mailArray);
}
if ($status_data[0] == 'Out for Delivery') {
                //$reportdata = $this->actionReportnew($_REQUEST['order_id'], $status_data[0], $email);
    $modelOrderline = new OrderLine;
    $buyername = $modelOrderline->buyername($modelOrder->attributes['user_id']);
    $from_email = 'grootsadmin@groots.in';
    $from_name = 'Groots Dashboard Admin';
    $subject = 'Groots Buyer Account';
    $urldata = Yii::app()->params['target_app_url'];
    $emailurldata = Yii::app()->params['email_app_url1'];
//                $body_html = 'Hi  <br/> your order id ' . $modelOrder->attributes['order_id'] . ' <br/> status now change<br/><br/><br> ,
//                                            <br/>' . $status_data[0] . ' <br/>';
    $body_html = '<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title>Email Verification </title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:400,600,300italic,700,700italic" rel="stylesheet" type="text/css">
  </head>
  <body style="margin: 0; padding: 0; font-family: sans-serif;">
      <table align="center"  cellpadding="0" border="0" cellspacing="0" width="600" style="border-collapse: collapse; display: block; border:0; background:#fff; ">
        <tbody>
            <tr style="display: block; ">
              <td style="padding:0px; width: 150px; background-color: #444;" >
                <a href="javascript:void(0);" style="display:block; height:63px; "><img src="' . $emailurldata . 'emailimage/logo.png" alt="" style="    width: 50px;
                    margin: 8px 20px;"></a>
                </td>
                <td style="padding: 5px 10px; width:450px; background-color:#444;color: #fff;font-size: 24px; text-transform: uppercase; text-align:right;">
                    <span style="float:right;">'. CUSTOMER_SUPPORT_NO. '</span>
                    <img src="' . $emailurldata . 'emailimage/callIco-head.png" alt="call" width="25" style="float:right; margin:0 10px;"> 
                </td>
            </tr>
            <tr>
              <td colspan="2" style="text-align:center;padding:20px 0; background:#fff url(' . $emailurldata . 'emailimage/bg-repeat.jpg) repeat-x;">
                <img src="' . $emailurldata . 'emailimage/check-shadow.png" alt="call" width="100" style=" margin:20px auto;"> 
            </td>
        </tr>
        <tr style="display: block;">
          <td colspan="2" style="display: block; padding: 10px;border: 1px solid #f7f7f7;border-width: 1px 2px 0;">
            <p style="font-size:20px;">
              <strong>Hi ' . $buyername . '</strong>
              <br> 
              <span style="margin-top:15px; display:block; font-size:14px; line-height:30px;">
                Your order (' . $modelOrder->attributes['order_number'] . ') is now   ' . $status_data[0] . '. Our delivery champ will call you in case of any requirement.<br>
                Thank you for choosing Groots!

            </span>
            <br>

            <a href="' . $urldata . '">
             <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
         </a>
         <br>
         <span style="font-size:14px;">Ordering: '. ORDER_SUPPORT_NO. '<br>
            Customer Support: '. CUSTOMER_SUPPORT_NO. '<br>
            Sales: '. SALES_SUPPORT_NO. '</span>
            <br> <br> 
        </p>
    </td>          
</tr>
<tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
  <td colspan="2" style="width: 600px;">
    <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
      <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
    </li>
</ul>
</td> 
</tr>
</tbody></table>
</body>
</html>';

$body_text = '';

$mailArray = array(
    'to' => array(
        '0' => array(
            'email' => "$email",
            )
        ),
    'from' => $from_email,
    'fromname' => $from_name,
    'subject' => $subject,
    'html' => $body_html,
    'text' => $body_text,
    'replyto' => $from_email,
    );
$mailsend = new OrderLine();
$resp = $mailsend->sgSendMail($mailArray);
}

if ($status_data[0] == 'Cancelled') {
                //$reportdata = $this->actionReportnew($_REQUEST['order_id'], $status_data[0], $email);
    $modelOrderline = new OrderLine;
    $buyername = $modelOrderline->buyername($modelOrder->attributes['user_id']);
    $from_email = 'grootsadmin@groots.in';
    $from_name = 'Groots Dashboard Admin';
    $subject = 'Groots Buyer Account';
    $urldata = Yii::app()->params['target_app_url'];
    $emailurldata = Yii::app()->params['email_app_url1'];
//                $body_html = 'Hi  <br/> your order id ' . $modelOrder->attributes['order_id'] . ' <br/> status now change<br/><br/><br> ,
//                                            <br/>' . $status_data[0] . ' <br/>';
    $body_html = '<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title>Email Verification </title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:400,600,300italic,700,700italic" rel="stylesheet" type="text/css">
  </head>
  <body style="margin: 0; padding: 0; font-family: sans-serif;">
      <table align="center"  cellpadding="0" border="0" cellspacing="0" width="600" style="border-collapse: collapse; display: block; border:0; background:#fff; ">
        <tbody>
            <tr style="display: block; ">
              <td style="padding:0px; width: 150px; background-color: #444;" >
                <a href="javascript:void(0);" style="display:block; height:63px; "><img src="' . $emailurldata . 'emailimage/logo.png" alt="" style="    width: 50px;
                    margin: 8px 20px;"></a>
                </td>
                <td style="padding: 5px 10px; width:450px; background-color:#444;color: #fff;font-size: 24px; text-transform: uppercase; text-align:right;">
                    <span style="float:right;">'. CUSTOMER_SUPPORT_NO. '</span>
                    <img src="' . $emailurldata . 'emailimage/callIco-head.png" alt="call" width="25" style="float:right; margin:0 10px;"> 
                </td>
            </tr>
            <tr>
              <td colspan="2" style="text-align:center;padding:20px 0; background:#fff url(' . $emailurldata . 'emailimage/bg-repeat.jpg) repeat-x;">
                <img src="' . $emailurldata . 'emailimage/check-shadow.png" alt="call" width="100" style=" margin:20px auto;"> 
            </td>
        </tr>
        <tr style="display: block;">
          <td colspan="2" style="display: block; padding: 10px;border: 1px solid #f7f7f7;border-width: 1px 2px 0;">
            <p style="font-size:20px;">
              <strong>Hi ' . $buyername . '</strong>
              <br> 
              <span style="margin-top:15px; display:block; font-size:14px; line-height:30px;">
                Sorry, we will not be able to service your order on Groots (' . $modelOrder->attributes['order_number'] . '). To know further details, please email on help@gogroots.in<br>
                Thank you for choosing Groots!

            </span>
            <br>

            <a href="' . $urldata . '">
             <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
         </a>
         <br>
         <span style="font-size:14px;">Ordering: '. ORDER_SUPPORT_NO. '<br>
            Customer Support: '. CUSTOMER_SUPPORT_NO. '<br>
            Sales: '. SALES_SUPPORT_NO. '</span>
            <br> <br> 
        </p>
    </td>          
</tr>
<tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
  <td colspan="2" style="width: 600px;">
    <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
      <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
    </li>
</ul>
</td> 
</tr>
</tbody></table>
</body>
</html>';

$body_text = '';

$mailArray = array(
    'to' => array(
        '0' => array(
            'email' => "$email",
            )
        ),
    'from' => $from_email,
    'fromname' => $from_name,
    'subject' => $subject,
    'html' => $body_html,
    'text' => $body_text,
    'replyto' => $from_email,
    );
$mailsend = new OrderLine();
$resp = $mailsend->sgSendMail($mailArray);
}



if ($status_data[0] == 'Paid') {
                //$reportdata = $this->actionReportnew($_REQUEST['order_id'], $status_data[0], $email);
    $modelOrderline = new OrderLine;
    $buyername = $modelOrderline->buyername($modelOrder->attributes['user_id']);
    $from_email = 'grootsadmin@groots.in';
    $from_name = 'Groots Dashboard Admin';
    $subject = 'Groots Buyer Account';
    $urldata = Yii::app()->params['target_app_url'];
    $emailurldata = Yii::app()->params['email_app_url1'];
//                $body_html = 'Hi  <br/> your order id ' . $modelOrder->attributes['order_id'] . ' <br/> status now change<br/><br/><br> ,
//                                            <br/>' . $status_data[0] . ' <br/>';
    $body_html = '<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title>Email Verification </title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:400,600,300italic,700,700italic" rel="stylesheet" type="text/css">
  </head>
  <body style="margin: 0; padding: 0; font-family: sans-serif;">
      <table align="center"  cellpadding="0" border="0" cellspacing="0" width="600" style="border-collapse: collapse; display: block; border:0; background:#fff; ">
        <tbody>
            <tr style="display: block; ">
              <td style="padding:0px; width: 150px; background-color: #444;" >
                <a href="javascript:void(0);" style="display:block; height:63px; "><img src="' . $emailurldata . 'emailimage/logo.png" alt="" style="    width: 50px;
                    margin: 8px 20px;"></a>
                </td>
                <td style="padding: 5px 10px; width:450px; background-color:#444;color: #fff;font-size: 24px; text-transform: uppercase; text-align:right;">
                    <span style="float:right;">'. CUSTOMER_SUPPORT_NO. '</span>
                    <img src="' . $emailurldata . 'emailimage/callIco-head.png" alt="call" width="25" style="float:right; margin:0 10px;"> 
                </td>
            </tr>
            <tr>
              <td colspan="2" style="text-align:center;padding:20px 0; background:#fff url(' . $emailurldata . 'emailimage/bg-repeat.jpg) repeat-x;">
                <img src="' . $emailurldata . 'emailimage/check-shadow.png" alt="call" width="100" style=" margin:20px auto;"> 
            </td>
        </tr>
        <tr style="display: block;">
          <td colspan="2" style="display: block; padding: 10px;border: 1px solid #f7f7f7;border-width: 1px 2px 0;">
            <p style="font-size:20px;">
              <strong>Hi ' . $buyername . '</strong>
              <br> 
              <span style="margin-top:15px; display:block; font-size:14px; line-height:30px;">
               We have received the payment for your order (' . $modelOrder->attributes['order_number'] . ').<br>
               Our team will reach out to you for any further update.<br>
               Thank you for choosing Groots!

           </span>
           <br>

           <a href="' . $urldata . '">
             <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
         </a>
         <br>
         <span style="font-size:14px;">Ordering: '. ORDER_SUPPORT_NO. '<br>
            Customer Support: '. CUSTOMER_SUPPORT_NO. '<br>
            Sales: '. SALES_SUPPORT_NO. '</span>
            <br> <br> 
        </p>
    </td>          
</tr>
<tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
  <td colspan="2" style="width: 600px;">
    <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
      <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
    </li>
</ul>
</td> 
</tr>
</tbody></table>
</body>
</html>';

$body_text = '';

$mailArray = array(
    'to' => array(
        '0' => array(
            'email' => "$email",
            )
        ),
    'from' => $from_email,
    'fromname' => $from_name,
    'subject' => $subject,
    'html' => $body_html,
    'text' => $body_text,
    'replyto' => $from_email,
    );
$mailsend = new OrderLine();
$resp = $mailsend->sgSendMail($mailArray);
}

if ($status_data[0] == 'Delivered') {
    $modelOrderline = new OrderLine;
    $buyername = $modelOrderline->buyername($modelOrder->attributes['user_id']);
                //$reportdata = $this->actionReportnew($_REQUEST['order_id'], $status_data[0], $email, 'invoice');
    $csv_name = 'order_' . $modelOrder->attributes['order_id'] . '.pdf';
    $csv_filename = "feeds/order_csv/" . $csv_name;
    $from_email = 'grootsadmin@groots.in';
    $from_name = 'Groots Dashboard Admin';
    $subject = 'Groots Buyer Account';
    $urldata = Yii::app()->params['email_app_url'];
    $emailurldata = Yii::app()->params['email_app_url1'];
//                $body_html = 'Hi  <br/> your order id ' . $modelOrder->attributes['order_id'] . ' <br/> status now change<br>:  ' . $status_data[0] . ',
//                                            <br/> <a href =' . $urldata . $modelOrder->attributes['order_id'] . '_' . md5('Order' . $modelOrder->attributes['order_id']) . '.' . 'pdf' . '> click here download invoice </a><br/>';
    $body_html = '<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title>Email Verification </title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:400,600,300italic,700,700italic" rel="stylesheet" type="text/css">
  </head>
  <body style="margin: 0; padding: 0; font-family: sans-serif;">
      <table align="center"  cellpadding="0" border="0" cellspacing="0" width="600" style="border-collapse: collapse; display: block; border:0; background:#fff; ">
        <tbody>
            <tr style="display: block; ">
              <td style="padding:0px; width: 150px; background-color: #444;" >
                <a href="javascript:void(0);" style="display:block; height:63px; "><img src="' . $emailurldata . 'emailimage/logo.png" alt="" style="    width: 50px;
                    margin: 8px 20px;"></a>
                </td>
                <td style="padding: 5px 10px; width:450px; background-color:#444;color: #fff;font-size: 24px; text-transform: uppercase; text-align:right;">
                    <span style="float:right;">'. CUSTOMER_SUPPORT_NO. '</span>
                    <img src="' . $emailurldata . 'emailimage/callIco-head.png" alt="call" width="25" style="float:right; margin:0 10px;"> 
                </td>
            </tr>
            <tr>
              <td colspan="2" style="text-align:center;padding:20px 0; background:#fff url(' . $emailurldata . 'emailimage/bg-repeat.jpg) repeat-x;">
                <img src="' . $emailurldata . 'emailimage/check-shadow.png" alt="call" width="100" style=" margin:20px auto;"> 
            </td>
        </tr>
        <tr style="display: block;">
          <td colspan="2" style="display: block; padding: 10px;border: 1px solid #f7f7f7;border-width: 1px 2px 0;">
            <p style="font-size:20px;">
              <strong>Hi ' . $buyername . '</strong>
              <br> 
              <span style="margin-top:15px; display:block; font-size:14px; line-height:30px;">
                Your order (' . $modelOrder->attributes['order_number'] . ') is has been ' . $status_data[0] . '. If you have a feedback, please email your concern to help@gogroots.in<br>
                Thank you for choosing Groots! <br>
                <br/> <a href =' . $urldata . $modelOrder->attributes['order_id'] . '_' . md5('Order' . $modelOrder->attributes['order_id']) . '.' . 'pdf' . '> Click here to download the invoice </a><br/>
            </span>
            <br>

            <a href="' . $urldata . '">
             <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
         </a>
         <br>
         <span style="font-size:14px;">Ordering: '. ORDER_SUPPORT_NO. '<br>
            Customer Support: '. CUSTOMER_SUPPORT_NO. '<br>
            Sales: '. SALES_SUPPORT_NO. '</span>
            <br> <br> 
        </p>
    </td>          
</tr>
<tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
  <td colspan="2" style="width: 600px;">
    <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
      <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
    </li>
</ul>
</td> 
</tr>
</tbody></table>
</body>
</html>';

$body_text = '';
$mailArray = array(
    'to' => array(
        '0' => array(
            'email' => "$email",
            )
        ),
    'from' => $from_email,
    'fromname' => $from_name,
    'subject' => $subject,
    'html' => $body_html,
    'text' => $body_text,
    'replyto' => $from_email,
    );
$mailsend = new OrderLine();
$resp = $mailsend->sgSendMail($mailArray);
}
if (isset($_POST['unit_price_discount']) && isset($_POST['unit_price_discount_old']) && !empty($_POST['orderline_ids_discount'])) {
    $no_records = count($_POST['unit_price_discount']);
    for ($p = 0; $p < $no_records; $p++) {

        $unit_price_discount = $_POST['unit_price_discount'][$p];
        $unit_price_discount_old = $_POST['unit_price_discount_old'][$p];
        $unit_price = $_POST['unit_price'][$p];
        $status_update = $status[$p];
        $orderline_ids_discount = $_POST['orderline_ids_discount'][$p];
                    //   die;
        if ($unit_price_discount != $unit_price_discount_old && $unit_price >= $unit_price_discount) {
            $lineinfo = $linedescinfo->Updatediscountquantity($unit_price_discount, $orderline_ids_discount);
            Yii::app()->user->setFlash('success', 'Unit Price updated Successfully.');
        }
    }
}
if (isset($_POST['uniq_order_size']) && isset($_POST['sizeqty']) && isset($_POST['sizeqty_old'])) {
    $no_records = count($_POST['uniq_order_size']);


    for ($i = 0; $i < $no_records; $i++) {
        $uniq_order_size = $_POST['uniq_order_size'][$i];
        $size_detail = explode('>', $uniq_order_size);
        $order_line_id = $size_detail[0];
        $baseproduct_id = $size_detail[1];
        if ($_POST['sizeqty'][$i] > 0) {
            $size_quantity = $_POST['sizeqty'][$i];
            $sizeqty_old = $_POST['sizeqty_old'][$i];
        } else {
            $size_quantity = $_POST['sizeqty_old'][$i];
            $sizeqty_old = $_POST['sizeqty_old'][$i];
        }
//                    $size_quantity = $_POST['sizeqty'][$i];
//                    $sizeqty_old = $_POST['sizeqty_old'][$i];
                    //  $unit_price_discount_qnt=$_POST['unit_price_discount'][$i];

        if ($sizeqty_old != $size_quantity) {
            $lineinfo = $linedescinfo->Updatesizequantity($order_line_id, $baseproduct_id, $size_quantity,$shipping_charge);
                        //$updateshipping = $linedescinforeport->updateShippingCharge($shipping_charge,$id);
            Yii::app()->user->setFlash('success', 'Size Quantity updated Successfully.');
        }
    }
}

            //  $oderdescinfo = new OrderHeader;
            //  $orderinfo = $oderdescinfo->updatelinedescById($_POST['order_id'], $_POST['grand_total'], $_POST['gtotal_price_discount']);



$this->redirect(array('OrderHeader/update', 'id' => $_POST['order_id'],));
            //  $this->redirect(array('OrderLine/update', 'id' => $_POST['id'], 'order_id' => $_POST['order_id'], 'status' => $_POST['Status'], 'unit_price_discount' => $_POST['unit_price_discount'], 'total_price_discount' => $_POST['total_price_discount']));
            // }
}
//echo '<pre>';print_r($_POST);die;
        #............update Shipping Address............
if (isset($_POST['update_shipping_address'])) {
    if (isset($_POST['shipping_name'])) {
        $modelOrder->shipping_name = $_POST['shipping_name'];
    }
    if (isset($_POST['shipping_address'])) {
        $modelOrder->shipping_address = $_POST['shipping_address'];
    }
    if (isset($_POST['shipping_city'])) {
        $modelOrder->shipping_city = $_POST['shipping_city'];
    }
    if (isset($_POST['shipping_state'])) {
        $modelOrder->shipping_state = $_POST['shipping_state'];
    }
    if (isset($_POST['shipping_phone'])) {
        $modelOrder->shipping_phone = $_POST['shipping_phone'];
    }
    if (isset($_POST['shipping_pincode'])) {
        $modelOrder->shipping_pincode = $_POST['shipping_pincode'];
    }

    if ($modelOrder->save()) {
        Yii::app()->user->setFlash('success', 'Shipping address updated Successfully.');
    } else {
        Yii::app()->user->setFlash('error', 'Invalid Entry.');
    }
}
        #...........End update Shipping Address............

$this->render('update', array(
    'model' => $model,
    'modelOrder' => $modelOrder
    ));
}

public function actionDispatch($id) {

    $model = OrderLine::model()->findAllByAttributes(array('order_id' => $id));
    $modelOrder = $this->loadModel($id);
    $dispatch_model = new Dispatch();
    $courier_name = '';
    $dispatched_date = '';
    $qty = array();
    $track_id = '';
    $remaining_qty = array();
    if (isset($_POST['save'])) {

        if (isset($_POST['order_id'])) {
            $order_id = $_POST['order_id'];
            $dispatch_model->order_id = $_POST['order_id'];
        }
        if (isset($_POST['subscribed_product'])) {
            $subscribed_product = $_POST['subscribed_product'];
        }
        if (isset($_POST['courier_name'])) {
            $courier_name = $_POST['courier_name'];
            $dispatch_model->courier_name = $_POST['courier_name'];
        }
        if (isset($_POST['dispatched_date'])) {
            $dispatched_date = $_POST['dispatched_date'];
            $dispatch_model->dispatched_date = $_POST['dispatched_date'];
        }
        if (isset($_POST['qty'])) {
            $qty = $_POST['qty'];
        }
        if (isset($_POST['remaining_qty'])) {
            $remaining_qty = $_POST['remaining_qty'];
        }
        if (isset($_POST['total_qty'])) {
            $total_qty = $_POST['total_qty'];
        }

        if (isset($_POST['track_id'])) {
            $track_id = $_POST['track_id'];
            $dispatch_model->track_id = $_POST['track_id'];
        }
            // $sql="INSERT INTO order_header (order_id,baseproduct_id,qty,track_id,dispatched_date,courier_name)VALUES ";
            // for( $i=0;$i<$count_record;$i++){
            // $sql .="('".$order_id."','".$baseproduct_id[$i]."','".$qty[$i]."','".$track_id."','".$dispatched_date."','".$courier_name."'),";
            // }   
            //  $sql=  rtrim($sql,',');
            // $succ= $dispatch_model->Insert_paertial_shipment($sql);
            //$dispatch_model->shipping_partner = $_POST['shipping_partner'];

        $flag = 0;
        $count_record = count($qty);


        for ($i = 0; $i < $count_record; $i++) {
            $dispatch_model->subscribed_product_id = $subscribed_product[$i];
            $dispatch_model->qty = $qty[$i];
            $addable = $remaining_qty[$i] + $qty[$i];

            if (($remaining_qty[$i] > 0) && ($qty[$i] > 0) && ($addable <= $total_qty[$i] || $remaining_qty[$i] == $total_qty[$i])) {
                if ($dispatch_model->save()) {
                    $flag++;
                }
            }
        }


        if ($flag > 0) {
            Yii::app()->user->setFlash('success', '' . $flag . ' Record Added Successfully.');
        } else if ($flag == 0) {
            Yii::app()->user->setFlash('error', 'Invalid Entry.');
        }
    }

    $this->render('dispatch', array(
        'model' => $model,
        'modelOrder' => $modelOrder,
        'dispatch_model' => $dispatch_model,
        'track_id' => $track_id,
        'qty' => $qty,
        'dispatched_date' => $dispatched_date,
        'courier_name' => $courier_name,
        ));
}

public function actionEdit($id) {
    $model = OrderLine::model()->findAllByAttributes(array('order_id' => $id));


        // $model1= new OrderLine; 
    if (isset($_POST['Update'])) {
        $this->redirect(array('OrderLine/update', 'id' => $_POST['id'], 'order_id' => $_POST['order_id'], 'status' => $_POST['Status']));
    }

    $this->render('edit', array(
        'model' => $model,
        'modelOrder' => $modelOrder,
        ));
}

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id) {
        $this->loadModel($id)->delete();

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    }

    /**
     * Lists all models.
     */
    public function actionIndex() {
        $dataProvider = new CActiveDataProvider('OrderHeader');
        $this->render('index', array(
            'dataProvider' => $dataProvider,
            ));
    }

    public function actionsale_summery() {
        $model = new SalesSummery('search_1');

        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['SalesSummery']))
            $model->attributes = $_GET['SalesSummery'];

        $this->render('sales_summery', array(
            'model' => $model,
            ));
    }

    public function actionsale_summery_detail() {
        $model = new SalesSummery('search_1');

        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['SalesSummery']))
            $model->attributes = $_GET['SalesSummery'];

        $this->render('sales_summery_detail', array(
            'model' => $model,
            ));
    }

    public function actionsale_by_style() {
        $model = new SaleByStyles('search_1');

        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['SaleByStyles']))
            $model->attributes = $_GET['SaleByStyles'];

        $this->render('sale_by_style', array(
            'model' => $model,
            ));
    }

    public function actionsale_by_style_detail() {
        $model = new SaleByStyles('search_1');

        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['SaleByStyles']))
            $model->attributes = $_GET['SaleByStyles'];

        $this->render('sale_by_style_detail', array(
            'model' => $model,
            ));
    }

    public function actionsales_by_retailer() {
        $model = new SaleByRetailer('search_1');

        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['SaleByRetailer']))
            $model->attributes = $_GET['SaleByRetailer'];

        $this->render('sales_by_retailer', array(
            'model' => $model,
            ));
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return OrderHeader the loaded model
     * @throws CHttpException
     */
    public function loadModel($id) {
        $model = OrderHeader::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    public function actionsales_by_retailer_detail() {
        $model = new OrderHeader('search');

        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['SaleByRetailer']))
            $model->attributes = $_GET['SaleByRetailer'];

        $this->render('sales_by_retailer_detail', array(
            'model' => $model,
            ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin() {
        
        if($this->checkAccessByData('WarehouseEditor', array('warehouse_id'=>1))){
        }
 //print("<pre>");
//die("here");
        $model = new OrderHeader('search');
        $w_id='';
        if(isset($_GET['w_id'])){
            $w_id = $_GET['w_id'];
        }


        if (isset($_GET['pageSize'])) {
            Yii::app()->user->setState('pageSize', (int) $_GET['pageSize']);
            unset($_GET['pageSize']);
        }
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['OrderHeader']))
            $model->attributes = $_GET['OrderHeader'];
        if(isset($w_id) and $w_id>0){
            $model->warehouse_id = $w_id;
        }
        if (isset($_POST['cancelbutton'])) {
            if (isset($_POST['selectedIds'])) {
                $no_of_selectedIds = count($_POST['selectedIds']);
                if ($no_of_selectedIds > 0) {
                    $order_ids = implode(',', $_POST['selectedIds']);
                    $active_record = $model->CancelOrderByID($order_ids);
                    if ($active_record) {
                        Yii::app()->user->setFlash('premission_info', 'Selected order list updated Successfully.');
                    } else {
                        Yii::app()->user->setFlash('premission_info', 'Please Try again.');
                    }
                }
            } else {
                Yii::app()->user->setFlash('premission_info', 'Please select at least one order.');
            }
        }
        if (isset($_GET['OrderHeader']))
            $model->attributes = $_GET['OrderHeader'];
        // echo '<pre>'; print_r ($_POST);die;

        if (isset($_POST['reportDownload']) && $_POST['reportDownload']!='') {
            $type = $_POST['reportDownload'];
            if (isset($_POST['selectedIds'])) {
                $no_of_selectedIds = count($_POST['selectedIds']);
                $pdfArray = array();
                if($type == 'email-invoice' && OrderHeader::ordersStatusDeliverd($_POST['selectedIds']) == 0){
                    Yii::app()->user->setFlash('error', 'Selected Order Status Not Delivered');
                    Yii::app()->controller->redirect("index.php?r=orderHeader/admin&w_id=".$w_id); 
                }
                else {
                    for ($i = 0; $i < $no_of_selectedIds; $i++) {
                        $pdf = $this->actionReport($_POST['selectedIds'][$i], $type, true);
                        if($type=='email-invoice'){

                            array_push($pdfArray, array('pdf'=>$pdf, 'order_id'=>$_POST['selectedIds'][$i]));
                        }
                        else{
                            array_push($pdfArray, $this->actionReport($_POST['selectedIds'][$i], $type, true));
                        }

                    }
                }
                if($type=='email-invoice'){
                    $this->sendMailToRetailer($pdfArray, false);
                    Yii::app()->controller->redirect("index.php?r=orderHeader/admin&w_id=".$w_id);
                }
                else{
                    $zipFileName=$type.".zip";
                    $this->zipFilesAndDownload($pdfArray,$zipFileName);
                }


            }
        }
        if (isset($_POST['status'])) {
            if (isset($_POST['selectedIds'])) {
                $no_of_selectedIds = count($_POST['selectedIds']);
                for ($i = 0; $i < $no_of_selectedIds; $i++) {
                    $connection = Yii::app()->secondaryDb;
                    $sql = "SELECT billing_email FROM order_header WHERE order_id ='" . $_POST['selectedIds'][$i] . "'";
                    $command = $connection->createCommand($sql);
                    $command->execute();
                    $emai_id = $command->queryAll();
                    $email = $emai_id['0']['billing_email'];
                    $connection = Yii::app()->secondaryDb;
                    $sql = "SELECT order_number FROM order_header WHERE order_id ='" . $_POST['selectedIds'][$i] . "'";
                    $command = $connection->createCommand($sql);
                    $command->execute();
                    $order_numberdata = $command->queryAll();
                    $order_number = $order_numberdata['0']['order_number'];

                    //$email= "kuldeep@canbrand.in";
                    if ($_POST['status1'] == 'Confirmed') {
                        //$reportdata = $this->actionReportnew($_POST['selectedIds'][$i], $_POST['status1'], $email);
                        $modelOrderline = new OrderLine;
                        $buyername = $modelOrderline->buyernamegrid($_POST['selectedIds'][$i]);
                        $from_email = 'grootsadmin@groots.in';
                        $from_name = 'Groots Dashboard Admin';
                        $subject = 'Groots Buyer Account';
                        $urldata = Yii::app()->params['target_app_url'];
                        $emailurldata = Yii::app()->params['email_app_url1'];
//                        $body_html = 'Hi  <br/> your order id ' . $_POST['selectedIds'][$i] . ' <br/> status now change<br>
//                                            <br/>: ' . $_POST['status1'] . '<br/>,';
                        $body_html = '<html xmlns="http://www.w3.org/1999/xhtml">
                        <head>
                          <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                          <title>Email Verification </title>
                          <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
                          <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:400,600,300italic,700,700italic" rel="stylesheet" type="text/css">
                      </head>
                      <body style="margin: 0; padding: 0; font-family: sans-serif;">
                          <table align="center"  cellpadding="0" border="0" cellspacing="0" width="600" style="border-collapse: collapse; display: block; border:0; background:#fff; ">
                            <tbody>
                                <tr style="display: block; ">
                                  <td style="padding:0px; width: 150px; background-color: #444;" >
                                    <a href="javascript:void(0);" style="display:block; height:63px; "><img src="' . $emailurldata . 'emailimage/logo.png" alt="" style="    width: 50px;
                                        margin: 8px 20px;"></a>
                                    </td>
                                    <td style="padding: 5px 10px; width:450px; background-color:#444;color: #fff;font-size: 24px; text-transform: uppercase; text-align:right;">
                                        <span style="float:right;">'. SALES_SUPPORT_NO. '</span>
                                        <img src="' . $emailurldata . 'emailimage/callIco-head.png" alt="call" width="25" style="float:right; margin:0 10px;"> 
                                    </td>
                                </tr>
                                <tr>
                                  <td colspan="2" style="text-align:center;padding:20px 0; background:#fff url(' . $emailurldata . 'emailimage/bg-repeat.jpg) repeat-x;">
                                    <img src="' . $emailurldata . 'emailimage/check-shadow.png" alt="call" width="100" style=" margin:20px auto;"> 
                                </td>
                            </tr>
                            <tr style="display: block;">
                              <td colspan="2" style="display: block; padding: 10px;border: 1px solid #f7f7f7;border-width: 1px 2px 0;">
                                <p style="font-size:20px;">
                                  <strong>Hi ' . $buyername . '</strong>
                                  <br> 
                                  <span style="margin-top:15px; display:block; font-size:14px; line-height:30px;">
                                    Your order (' . $order_number . ') has been ' . $_POST['status1'] . '. We will keep you posted for further updates.  <br>
                                    Thank you for choosing Groots!
                                </span>
                                <br>

                                <a href="' . $urldata . '">
                                 <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
                             </a>
                             <br>
                             <span style="font-size:14px;">Ordering: '. ORDER_SUPPORT_NO. '<br>
                                Customer Support: '. CUSTOMER_SUPPORT_NO. '<br>
                                Sales: '. SALES_SUPPORT_NO. '</span>
                                <br> <br> 
                            </p>
                        </td>          
                    </tr>
                    <tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
                      <td colspan="2" style="width: 600px;">
                        <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
                          <li style="display:block; width:170px; float:left; text-align:center;">
                            <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
                        </li>
                        <li style="display:block; width:170px; float:left; text-align:center;">
                            <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
                        </li>
                        <li style="display:block; width:170px; float:left; text-align:center;">
                            <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
                        </li>
                    </ul>
                </td> 
            </tr>
        </tbody></table>
    </body>
    </html>';
    $body_text = '';

    $mailArray = array(
        'to' => array(
            '0' => array(
                'email' => "$email",
                )
            ),
        'from' => $from_email,
        'fromname' => $from_name,
        'subject' => $subject,
        'html' => $body_html,
        'text' => $body_text,
        'replyto' => $from_email,
        );
    $mailsend = new OrderLine();
    //$resp = $mailsend->sgSendMail($mailArray);
}

if ($_POST['status1'] == 'Paid') {
                        //$reportdata = $this->actionReportnew($_POST['selectedIds'][$i], $_POST['status1'], $email);
    $modelOrderline = new OrderLine;
    $buyername = $modelOrderline->buyernamegrid($_POST['selectedIds'][$i]);
    $from_email = 'grootsadmin@groots.in';
    $from_name = 'Groots Dashboard Admin';
    $subject = 'Groots Buyer Account';
    $urldata = Yii::app()->params['target_app_url'];
    $emailurldata = Yii::app()->params['email_app_url1'];
//                        $body_html = 'Hi  <br/> your order id ' . $_POST['selectedIds'][$i] . ' <br/> status now change<br>
//                                            <br/>: ' . $_POST['status1'] . '<br/>,';
    $body_html = '<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title>Email Verification </title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:400,600,300italic,700,700italic" rel="stylesheet" type="text/css">
  </head>
  <body style="margin: 0; padding: 0; font-family: sans-serif;">
      <table align="center"  cellpadding="0" border="0" cellspacing="0" width="600" style="border-collapse: collapse; display: block; border:0; background:#fff; ">
        <tbody>
            <tr style="display: block; ">
              <td style="padding:0px; width: 150px; background-color: #444;" >
                <a href="javascript:void(0);" style="display:block; height:63px; "><img src="' . $emailurldata . 'emailimage/logo.png" alt="" style="    width: 50px;
                    margin: 8px 20px;"></a>
                </td>
                <td style="padding: 5px 10px; width:450px; background-color:#444;color: #fff;font-size: 24px; text-transform: uppercase; text-align:right;">
                    <span style="float:right;">'. SALES_SUPPORT_NO. '</span>
                    <img src="' . $emailurldata . 'emailimage/callIco-head.png" alt="call" width="25" style="float:right; margin:0 10px;"> 
                </td>
            </tr>
            <tr>
              <td colspan="2" style="text-align:center;padding:20px 0; background:#fff url(' . $emailurldata . 'emailimage/bg-repeat.jpg) repeat-x;">
                <img src="' . $emailurldata . 'emailimage/check-shadow.png" alt="call" width="100" style=" margin:20px auto;"> 
            </td>
        </tr>
        <tr style="display: block;">
          <td colspan="2" style="display: block; padding: 10px;border: 1px solid #f7f7f7;border-width: 1px 2px 0;">
            <p style="font-size:20px;">
              <strong>Hi ' . $buyername . '</strong>
              <br> 
              <span style="margin-top:15px; display:block; font-size:14px; line-height:30px;">
                We have received the payment for your order (' . $order_number . ') .<br>Our team will reach out to you for any further update.<br>
                Thank you for choosing Groots!
            </span>
            <br>

            <a href="' . $urldata . '">
             <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
         </a>
         <br>
         <span style="font-size:14px;">Ordering: '. ORDER_SUPPORT_NO. '<br>
            Customer Support: '. CUSTOMER_SUPPORT_NO. '<br>
            Sales: '. SALES_SUPPORT_NO. '</span>
            <br> <br> 
        </p>
    </td>          
</tr>
<tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
  <td colspan="2" style="width: 600px;">
    <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
      <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
    </li>
</ul>
</td> 
</tr>
</tbody></table>
</body>
</html>';
$body_text = '';

$mailArray = array(
    'to' => array(
        '0' => array(
            'email' => "$email",
            )
        ),
    'from' => $from_email,
    'fromname' => $from_name,
    'subject' => $subject,
    'html' => $body_html,
    'text' => $body_text,
    'replyto' => $from_email,
    );
$mailsend = new OrderLine();
//$resp = $mailsend->sgSendMail($mailArray);
}


if ($_POST['status1'] == 'Cancelled') {
                        //$reportdata = $this->actionReportnew($_POST['selectedIds'][$i], $_POST['status1'], $email);
    $modelOrderline = new OrderLine;
    $buyername = $modelOrderline->buyernamegrid($_POST['selectedIds'][$i]);
    $from_email = 'grootsadmin@groots.in';
    $from_name = 'Groots Dashboard Admin';
    $subject = 'Groots Buyer Account';
    $urldata = Yii::app()->params['target_app_url'];
    $emailurldata = Yii::app()->params['email_app_url1'];
//                        $body_html = 'Hi  <br/> your order id ' . $_POST['selectedIds'][$i] . ' <br/> status now change<br>
//                                            <br/>: ' . $_POST['status1'] . '<br/>,';
    $body_html = '<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title>Email Verification </title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:400,600,300italic,700,700italic" rel="stylesheet" type="text/css">
  </head>
  <body style="margin: 0; padding: 0; font-family: sans-serif;">
      <table align="center"  cellpadding="0" border="0" cellspacing="0" width="600" style="border-collapse: collapse; display: block; border:0; background:#fff; ">
        <tbody>
            <tr style="display: block; ">
              <td style="padding:0px; width: 150px; background-color: #444;" >
                <a href="javascript:void(0);" style="display:block; height:63px; "><img src="' . $emailurldata . 'emailimage/logo.png" alt="" style="    width: 50px;
                    margin: 8px 20px;"></a>
                </td>
                <td style="padding: 5px 10px; width:450px; background-color:#444;color: #fff;font-size: 24px; text-transform: uppercase; text-align:right;">
                    <span style="float:right;">'. SALES_SUPPORT_NO. '</span>
                    <img src="' . $emailurldata . 'emailimage/callIco-head.png" alt="call" width="25" style="float:right; margin:0 10px;"> 
                </td>
            </tr>
            <tr>
              <td colspan="2" style="text-align:center;padding:20px 0; background:#fff url(' . $emailurldata . 'emailimage/bg-repeat.jpg) repeat-x;">
                <img src="' . $emailurldata . 'emailimage/check-shadow.png" alt="call" width="100" style=" margin:20px auto;"> 
            </td>
        </tr>
        <tr style="display: block;">
          <td colspan="2" style="display: block; padding: 10px;border: 1px solid #f7f7f7;border-width: 1px 2px 0;">
            <p style="font-size:20px;">
              <strong>Hi ' . $buyername . '</strong>
              <br> 
              <span style="margin-top:15px; display:block; font-size:14px; line-height:30px;">
                Sorry, we will not be able to service your order on Groots (' . $order_number . '). To know further details, please email on help@gogroots.in <br>
                Thank you for choosing Groots!
            </span>
            <br>

            <a href="' . $urldata . '">
             <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
         </a>
         <br>
         <span style="font-size:14px;">Ordering: '. ORDER_SUPPORT_NO. '<br>
            Customer Support: '. CUSTOMER_SUPPORT_NO. '<br>
            Sales: '. SALES_SUPPORT_NO. '</span>
            <br> <br> 
        </p>
    </td>          
</tr>
<tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
  <td colspan="2" style="width: 600px;">
    <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
      <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
    </li>
</ul>
</td> 
</tr>
</tbody></table>
</body>
</html>';
$body_text = '';

$mailArray = array(
    'to' => array(
        '0' => array(
            'email' => "$email",
            )
        ),
    'from' => $from_email,
    'fromname' => $from_name,
    'subject' => $subject,
    'html' => $body_html,
    'text' => $body_text,
    'replyto' => $from_email,
    );
$mailsend = new OrderLine();
//$resp = $mailsend->sgSendMail($mailArray);
}
if ($_POST['status1'] == 'Out for Delivery') {
                        //$reportdata = $this->actionReportnew($_POST['selectedIds'][$i], $_POST['status1'], $email);
    $modelOrderline = new OrderLine;
    $buyername = $modelOrderline->buyernamegrid($_POST['selectedIds'][$i]);
    $from_email = 'grootsadmin@groots.in';
    $from_name = 'Groots Dashboard Admin';
    $subject = 'Groots Buyer Account';
    $urldata = Yii::app()->params['target_app_url'];
    $emailurldata = Yii::app()->params['email_app_url1'];
//                        $body_html = 'Hi  <br/> your order id ' . $_POST['selectedIds'][$i] . ' <br/> status now change<br>
//                                            <br/>: ' . $_POST['status1'] . '<br/>,';
    $body_html = '<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title>Email Verification </title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:400,600,300italic,700,700italic" rel="stylesheet" type="text/css">
  </head>
  <body style="margin: 0; padding: 0; font-family: sans-serif;">
      <table align="center"  cellpadding="0" border="0" cellspacing="0" width="600" style="border-collapse: collapse; display: block; border:0; background:#fff; ">
        <tbody>
            <tr style="display: block; ">
              <td style="padding:0px; width: 150px; background-color: #444;" >
                <a href="javascript:void(0);" style="display:block; height:63px; "><img src="' . $emailurldata . 'emailimage/logo.png" alt="" style="    width: 50px;
                    margin: 8px 20px;"></a>
                </td>
                <td style="padding: 5px 10px; width:450px; background-color:#444;color: #fff;font-size: 24px; text-transform: uppercase; text-align:right;">
                    <span style="float:right;">'. SALES_SUPPORT_NO. '</span>
                    <img src="' . $emailurldata . 'emailimage/callIco-head.png" alt="call" width="25" style="float:right; margin:0 10px;"> 
                </td>
            </tr>
            <tr>
              <td colspan="2" style="text-align:center;padding:20px 0; background:#fff url(' . $emailurldata . 'emailimage/bg-repeat.jpg) repeat-x;">
                <img src="' . $emailurldata . 'emailimage/check-shadow.png" alt="call" width="100" style=" margin:20px auto;"> 
            </td>
        </tr>
        <tr style="display: block;">
          <td colspan="2" style="display: block; padding: 10px;border: 1px solid #f7f7f7;border-width: 1px 2px 0;">
            <p style="font-size:20px;">
              <strong>Hi ' . $buyername . '</strong>
              <br> 
              <span style="margin-top:15px; display:block; font-size:14px; line-height:30px;">
               Your order  (' . $order_number . ')  is now ' . $_POST['status1'] . '. Our delivery champ will call you in case of any requirement. <br>
               Thank you for choosing Groots!
           </span>
           <br>

           <a href="' . $urldata . '">
             <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
         </a>
         <br>
         <span style="font-size:14px;">Ordering: '. ORDER_SUPPORT_NO. '<br>
            Customer Support: '. CUSTOMER_SUPPORT_NO. '<br>
            Sales: '. SALES_SUPPORT_NO. '</span>
            <br> <br> 
        </p>
    </td>          
</tr>
<tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
  <td colspan="2" style="width: 600px;">
    <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
      <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
    </li>
</ul>
</td> 
</tr>
</tbody></table>
</body>
</html>';
$body_text = '';

$mailArray = array(
    'to' => array(
        '0' => array(
            'email' => "$email",
            )
        ),
    'from' => $from_email,
    'fromname' => $from_name,
    'subject' => $subject,
    'html' => $body_html,
    'text' => $body_text,
    'replyto' => $from_email,
    );
$mailsend = new OrderLine();
//$resp = $mailsend->sgSendMail($mailArray);
}

if ($_POST['status1'] == 'Delivered') {
                        //$reportdata = $this->actionReportnew($_POST['selectedIds'][$i], $_POST['status1'], $email, 'invoice');
    $modelOrderline = new OrderLine;
    $buyername = $modelOrderline->buyernamegrid($_POST['selectedIds'][$i]);
    $csv_name = 'order_' . $_POST['selectedIds'][$i] . '.pdf';
    $csv_filename = "feeds/order_csv/" . $csv_name;
    $from_email = 'grootsadmin@groots.in';
    $from_name = 'Groots Dashboard Admin';
    $subject = 'Groots Buyer Account';
    $urldata = Yii::app()->params['email_app_url'];
    $emailurldata = Yii::app()->params['email_app_url1'];
//                        $body_html = 'Hi  <br/> your order id ' . $_POST['selectedIds'][$i] . ' <br/> status now change<br>:  ' . $_POST['status1'] . ',
//                                            <br/> <a href =' . $urldata . $_POST['selectedIds'][$i] . '_' . md5('Order' . $_POST['selectedIds'][$i]) . '.' . 'pdf' . '> click here download invoice </a><br/>';
    $body_html = '<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
      <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
      <title>Email Verification </title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
      <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:400,600,300italic,700,700italic" rel="stylesheet" type="text/css">
  </head>
  <body style="margin: 0; padding: 0; font-family: sans-serif;">
      <table align="center"  cellpadding="0" border="0" cellspacing="0" width="600" style="border-collapse: collapse; display: block; border:0; background:#fff; ">
        <tbody>
            <tr style="display: block; ">
              <td style="padding:0px; width: 150px; background-color: #444;" >
                <a href="javascript:void(0);" style="display:block; height:63px; "><img src="' . $emailurldata . 'emailimage/logo.png" alt="" style="    width: 50px;
                    margin: 8px 20px;"></a>
                </td>
                <td style="padding: 5px 10px; width:450px; background-color:#444;color: #fff;font-size: 24px; text-transform: uppercase; text-align:right;">
                    <span style="float:right;">'. SALES_SUPPORT_NO. '</span>
                    <img src="' . $emailurldata . 'emailimage/callIco-head.png" alt="call" width="25" style="float:right; margin:0 10px;"> 
                </td>
            </tr>
            <tr>
              <td colspan="2" style="text-align:center;padding:20px 0; background:#fff url(' . $emailurldata . 'emailimage/bg-repeat.jpg) repeat-x;">
                <img src="' . $emailurldata . 'emailimage/check-shadow.png" alt="call" width="100" style=" margin:20px auto;"> 
            </td>
        </tr>
        <tr style="display: block;">
          <td colspan="2" style="display: block; padding: 10px;border: 1px solid #f7f7f7;border-width: 1px 2px 0;">
            <p style="font-size:20px;">
              <strong>Hi ' . $buyername . '</strong>
              <br> 
              <span style="margin-top:15px; display:block; font-size:14px; line-height:30px;">
                Your order (' . $order_number . ') is has been ' . $_POST['status1'] . '. If you have a feedback, please email your concern to help@gogroots.in<br>
                Thank you for choosing Groots!<br>
                <br/> <a href =' . $urldata . $_POST['selectedIds'][$i] . '_' . md5('Order' . $_POST['selectedIds'][$i]) . '.' . 'pdf' . '> Click here to download invoice </a><br/>
            </span>
            <br>

            <a href="' . $urldata . '">
             <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
         </a>
         <br>
         <span style="font-size:14px;">Ordering: '. ORDER_SUPPORT_NO. '<br>
            Customer Support: '. CUSTOMER_SUPPORT_NO. '<br>
            Sales: '. SALES_SUPPORT_NO. '</span>
            <br> <br> 
        </p>
    </td>          
</tr>
<tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
  <td colspan="2" style="width: 600px;">
    <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
      <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
    </li>
    <li style="display:block; width:170px; float:left; text-align:center;">
        <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
    </li>
</ul>
</td> 
</tr>
</tbody></table>
</body>
</html>';

$body_text = '';
$mailArray = array(
    'to' => array(
        '0' => array(
            'email' => "$email",
            )
        ),
    'from' => $from_email,
    'fromname' => $from_name,
    'subject' => $subject,
    'html' => $body_html,
    'text' => $body_text,
    'replyto' => $from_email,
    );
$mailsend = new OrderLine();
//$resp = $mailsend->sgSendMail($mailArray);
}
}
if ($no_of_selectedIds > 0) {
    $status_order = $_POST['status1'];
    $order_ids = implode(',', $_POST['selectedIds']);
    if ($status_order != 'Change status') {
        $active_record = $model->StatusOrderByID($order_ids, $status_order);
        if($status_order == 'Delivered'){
            $model->setFeedbackStatusOnDelivered($order_ids, 'Pending');
        }
        if($status_order != 'Delivered'){
            $model->setFeedbackStatusOnDelivered($order_ids, 'Not Required');
        } 

                        //$active_record = $model->CancelOrderByID($order_ids);
        if ($active_record) {
            Yii::app()->user->setFlash('success', 'Selected order id status updated successfully.');
        } else {
            Yii::app()->user->setFlash('premission_info', 'Selected order id status already ' . $status_order . '');
        }
    } else {
        Yii::app()->user->setFlash('premission_info', 'Selected order id status not selected');
    }
}
} else {
    Yii::app()->user->setFlash('premission_info', 'Please select at least one order.');
}
}

if (isset($_POST['downloadbutton'])) {

    if (isset($_POST['selectedIds'])) {
        $no_of_selectedIds = count($_POST['selectedIds']);
        if ($no_of_selectedIds > 0) {
            $order_ids = implode(',', $_POST['selectedIds']);
            ob_clean();
            $model->downloadCSVByIDs($order_ids);
            ob_flush();
            exit();
        }
    } else {
        $sub_ids = $model->allcheckproductlcsv();
        if (count($sub_ids) > 0) {
            for ($i = 0; $i < count($sub_ids); $i++) {
                $subpro_id[] = implode(',', $sub_ids[$i]);
            }
            if (count($sub_ids) > 0) {
                        //echo "hello222";die;
                $subpro_id_new = implode(',', $subpro_id);
            }
        }
        ob_clean();
        $response = $model->downloadCSVByIDs($subpro_id_new);
        ob_flush();


                // exit();
    }
}
if (isset($_POST['sandbutton'])) {
    if (isset($_POST['selectedIds'])) {
        $no_of_selectedIds = count($_POST['selectedIds']);
        if ($no_of_selectedIds > 0) {
            $order_ids = implode(',', $_POST['selectedIds']);
            ob_clean();
            $response = $model->downloadCSVByID($order_ids);
            ob_flush();
            exit();
        }
    } else {
        $sub_ids = $model->allcheckproductlcsv();
        if (count($sub_ids) > 0) {
            for ($i = 0; $i < count($sub_ids); $i++) {
                $subpro_id[] = implode(',', $sub_ids[$i]);
            }
            if (count($sub_ids) > 0) {
                        //echo "hello222";die;
                $subpro_id_new = implode(',', $subpro_id);
            }
        }
        ob_clean();
        $response = $model->downloadCSVByID($subpro_id_new);
        ob_flush();


                // exit();
    }
            // Yii::app()->user->setFlash('premission_info', 'done.');
}
if(isset($_REQUEST['status']) && Yii::app()->session['sttus_sess'] != "")
{
  $model->setAttribute('status', $_REQUEST['status']);
  Yii::app()->session['sttus_sess'] = "";
}

$this->render('admin', array(
    'model' => $model,
    ));
}



    /**
     * Performs the AJAX validation.
     * @param OrderHeader $model the model to be validated
     */
    protected function performAjaxValidation($model) {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'order-header-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    public function createPdf($model,$newModel,$modelOrder,$retailer,$type,$zip ){


        //die("here23");
        // var_dump($newModel);
        // var_dump($model);die;
        ob_start();
        echo $this->renderPartial('reportviewcontent' ,array('model' => $model,'newModel' => $newModel,
            'modelOrder' =>$modelOrder, 'retailer'=>$retailer, 'type'=>$type), true);//die;
        $content = ob_get_clean();
        require_once( dirname(__FILE__) . '/../extensions/html2pdf/html2pdf.php');
        $title = "";
        if($type=="dc"){
            $title = "Delivery Challan Groots Admin Panel";
        }
        else{
            $title = "Invoice Order Groots Admin Panel";
        }
        $downloadFileName=$retailer->name." (".substr($modelOrder->delivery_date, 0, 10).")"." ". $modelOrder->order_id.".pdf";

        try
        {
            $html2pdf = new HTML2PDF('P', 'A4', 'en');
            $html2pdf->pdf->SetTitle($title);
//      $html2pdf->setModeDebug();
            //  $html2pdf->setDefaultFont('Arial');
            $html2pdf->writeHTML($content, isset($_GET['vuehtml']));
            //echo $zip; die("here2"); 
            if($zip==true){
                return array('pdf'=>$html2pdf, 'name'=>$downloadFileName);
            }
            else{
                $html2pdf->Output($downloadFileName);
                var_dump($html2pdf);
            }


        }
        catch(HTML2PDF_exception $e) {
            echo $e;
            exit;
        }
    }

    public function actionReport($id, $type, $zip=false) {
        // echo "hello";die;
        /*$model = OrderLine::model()->findAllByAttributes(array('order_id' => $id,
        ),array('order'=>'product_name ASC'));*/

        $model = OrderLine::getOrderLinebyOrderId($id);
        $prodIds= array();
        foreach ($model as $key => $value) {
            array_push($prodIds, $value['base_product_id']);
        }
        $newModel = array();
        $prodIdsString = implode(',', $prodIds);
        $catNameCatId = Category::getCatNameIdbyProdId($prodIdsString);
        foreach ($model as $key => $value) {
            $value['category_name'] = $catNameCatId[$value['base_product_id']]['category_name'];
            $model[$key] = $value;
            if(! array_key_exists($value['category_name'], $newModel)){
                $newModel[$value['category_name']] = array();
            }
            array_push($newModel[$value['category_name']], $value);
        }
        ksort($newModel, SORT_STRING);
        $modelOrder = $this->loadModel($id);
        $store = Store::model()->findByAttributes(array('store_id' => 1));
        $modelOrder->groots_address = $store->business_address;
        $modelOrder->groots_city = $store->business_address_city;
        $modelOrder->groots_state = $store->business_address_state;
        $modelOrder->groots_country = $store->business_address_country;
        $modelOrder->groots_pincode = $store->business_address_pincode;
        $modelOrder->groots_authorized_name = $store->store_name;
        $retailer = Retailer::model()->findByPk($modelOrder->user_id);
        if($zip==true){
            return $this->createPdf($model,$newModel,$modelOrder,$retailer,$type,$zip );
        }
        $this->createPdf($model,$newModel,$modelOrder,$retailer,$type,$zip );

    }

    public function actionReportnew($id, $status, $email, $type) {
        //  echo $status;die;
        //$model = OrderLine::model()->findAllByAttributes(array('order_id' => $id));
      $model = OrderLine::model()->findAllByAttributes(array('order_id' => $id,
        ),array('order'=>'product_name ASC'));
      $modelOrder = $this->loadModel($id);
      $store_model = new Store();
      $this->renderPartial('reportviewdata', array(
        'model' => $model,
        'modelOrder' => $modelOrder,
        'status' => $status,
        'email' => $email,
        'store_model' => $store_model,
        'type'=> $type,
        ));
        //$this->renderPartial("reportview");
  }


  public function actionProductPricesByRetailerAndDate(){
        //echo "<pre>";
    $effectiveDate = $_GET['date'];
    $retailerId =  $_GET['retailerId'];
    $productPrices = ProductPrice::getRetailerSubscribedProductPricesByDate($retailerId, $effectiveDate);
    $effective_prices = self::getEffectivePrice($retailerId, $effectiveDate);
    $sProdIdPriceArray = array();
    foreach ($productPrices as $productPrice){

        $effectivePriceSet = (isset($effective_prices[$productPrice['base_product_id']]) && $effective_prices[$productPrice['base_product_id']] > 0);

        $sProdIdPriceArray[$productPrice['base_product_id']] = ( $effectivePriceSet ? $effective_prices[$productPrice['base_product_id']] : $productPrice['store_offer_price']);
    }
    echo json_encode($sProdIdPriceArray);

}

public function zipFilesAndDownload($file_names,$archive_file_name)
{
    //var_dump($file_names);die;
    $dir = dirname(__FILE__) . '/../../../../dump/';
    $zipName = $dir.$archive_file_name;

        $files = glob($dir."*"); // get all file names
        foreach($files as $file){ // iterate files
            if(is_file($file))
                unlink($file); // delete file
        }
        $zip = new ZipArchive();
        //create the file and throw the error if unsuccessful
        if ($zip->open($zipName, ZIPARCHIVE::CREATE )!==TRUE) {
            exit("cannot open <$zipName>\n");
        }
        //add each files of $file_name array to archive

        foreach($file_names as $file)
        {
            $pdf = $file['pdf'];
            $name =$file['name'];
            $fullName = $dir.$name;
            $pdf->Output($fullName, 'F');
            $zip->addFile($fullName, $name);
            //echo $file_path.$files,$files."

        }
        $zip->close();


        //then send the headers to force download the zip file
        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=$archive_file_name");
        header("Content-length: " . filesize($zipName));
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile($zipName);


        exit;
        //var_dump($zipName);
    }

    private function sendMailToRetailer($pdfArray, $skuShort){
        $array = array();
        //var_dump($pdfArray);die;
        foreach ($pdfArray as $each){
            $pdf = (isset($each['pdf'])) ? $each['pdf']: null;
            $order_id = $each['order_id'];

            $connection = Yii::app()->secondaryDb;
            $sql = "SELECT oh.billing_email,re.alternate_email, oh.user_id, oh.delivery_date, re.name, wa.email_group FROM order_header as oh left join cb_dev_groots.retailer re on re.id = oh.user_id left join cb_dev_groots.warehouses as wa 
                on wa.id = re.allocated_warehouse_id WHERE order_id = '" . $order_id. "'";
            $command = $connection->createCommand($sql);
            $command->execute();
            $result = $command->queryAll();
            foreach ($result as $key => $value) {
                $emai_id = $value['billing_email'];
                $alternate_email = $value['alternate_email'];
                $retailerId = $value['user_id'];
                $retailerName = $value['name'];
                $delivery_date = $value['delivery_date'];
                $cc_group = $value['email_group'];
            }

            $modelOrderline = new OrderLine;
            $buyername = $modelOrderline->buyernamegrid($order_id);
            //var_dump($buyername);die;
            $csv_name = 'order_' . $order_id . '.pdf';
            $csv_filename = "feeds/order_csv/" . $csv_name;
            $from_email = EMAIL_SENDER;
            $replyto = REPLY_TO_EMAIL;
            $from_name = 'Groots Dashboard Admin';

            //$urldata = Yii::app()->params['email_app_url'];
            $emailurldata = Yii::app()->params['email_app_url1'];
            //die($emailurldata);die;
            $email_message = '';
            $subject = '';
            if($skuShort['isShort']){
                $email_message = $skuShort['message'];
                $subject = "Groots-Short Delivery Information";
            }
            else{
                $email_message = 'Your order (' . $order_id . ') has been delivered and the Invoice is attached in this Mail. '. 'If you have a feedback, please email your concern to '.$replyto.'<br>
                        Thank you for choosing Groots!<br>';
                $subject = 'Order Invoice-'.$retailerName.' ('.$delivery_date.')';
            }

//                        $body_html = 'Hi  <br/> your order id ' . $_POST['selectedIds'][$i] . ' <br/> status now change<br>:  ' . $_POST['status1'] . ',
//                                            <br/> <a href =' . $urldata . $_POST['selectedIds'][$i] . '_' . md5('Order' . $_POST['selectedIds'][$i]) . '.' . 'pdf' . '> click here download invoice </a><br/>';
            $body_html = '<html xmlns="http://www.w3.org/1999/xhtml">
            <head>
              <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
              <title>Email Verification </title>
              <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
              <link href="https://fonts.googleapis.com/css?family=Josefin+Sans:400,600,300italic,700,700italic" rel="stylesheet" type="text/css">
          </head>
          <body style="margin: 0; padding: 0; font-family: sans-serif;">
              <table align="center"  cellpadding="0" border="0" cellspacing="0" width="600" style="border-collapse: collapse; display: block; border:0; background:#fff; ">
                <tbody>
                    <tr style="display: block; ">
                      <td style="padding:0px; width: 150px; background-color: #444;" >
                        <a href="javascript:void(0);" style="display:block; height:63px; "><img src="' . $emailurldata . 'emailimage/logo.png" alt="" style="    width: 50px;
                            margin: 8px 20px;"></a>
                        </td>
                        <td style="padding: 5px 10px; width:450px; background-color:#444;color: #fff;font-size: 24px; text-transform: uppercase; text-align:right;">
                            <span style="float:right;">'. SALES_SUPPORT_NO. '</span>
                            <img src="' . $emailurldata . 'emailimage/callIco-head.png" alt="call" width="25" style="float:right; margin:0 10px;"> 
                        </td>
                    </tr>
                    <tr>
                      <td colspan="2" style="text-align:center;padding:20px 0; background:#fff url(' . $emailurldata . 'emailimage/bg-repeat.jpg) repeat-x;">
                        <img src="' . $emailurldata . 'emailimage/check-shadow.png" alt="call" width="100" style=" margin:20px auto;"> 
                    </td>
                </tr>
                <tr style="display: block;">
                  <td colspan="2" style="display: block; padding: 10px;border: 1px solid #f7f7f7;border-width: 1px 2px 0;">
                    <p style="font-size:20px;">
                      <strong>Hi ' . $buyername . '</strong>
                      <br> 
                      <span style="margin-top:15px; display:block; font-size:14px; line-height:30px;">
                        '.$email_message.'
                    </span>
                    <br>

                    <a href="' . APP_LINK . '">
                     <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
                 </a>
                 <br>
                 <span style="font-size:14px;">Ordering: '. ORDER_SUPPORT_NO. '<br>
                    Customer Support: '. CUSTOMER_SUPPORT_NO. '<br>
                    Sales: '. SALES_SUPPORT_NO. '</span>
                    <br> <br> 
                </p>
            </td>          
        </tr>
        <tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
          <td colspan="2" style="width: 600px;">
            <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
              <li style="display:block; width:170px; float:left; text-align:center;">
                <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
            </li>
            <li style="display:block; width:170px; float:left; text-align:center;">
                <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
            </li>
            <li style="display:block; width:170px; float:left; text-align:center;">
                <a href="http://www.gogroots.com/" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
            </li>
        </ul>
    </td> 
</tr>
</tbody></table>
</body>
</html>';

$body_text = '';
if(isset($alternate_email) && !empty($alternate_email)){
    $emai_id.= ','.$alternate_email;
}
if($skuShort['isShort']){
    $mailArray = array(
        'to' => $emai_id,
        'from' => $from_email,
        'fromname' => $from_name,
        'subject' => $subject,
        'html' => $body_html,
        'text' => $body_text,
        'replyTo' => $replyto,
        //'pdf' => $pdf,
        'cc' => $cc_group,
    );
}
else {
    $mailArray = array(
        'to' => $emai_id,
        'from' => $from_email,
        'fromname' => $from_name,
        'subject' => $subject,
        'html' => $body_html,
        'text' => $body_text,
        'replyTo' => $replyto,
        'pdf' => $pdf,
        'cc' => $cc_group,
    );
}
array_push($array, $mailArray);


}
$mailsend = new OrderLine();
try{
    $resp = $mailsend->awsAttachmentMail($array);
    Yii::app()->user->setFlash('success','Email Sent Successfully' );
    //Yii::app()->controller->redirect("index.php?r=orderHeader/admin");    
} catch (Exception $e){
    $x = "The email was not sent. Error message: ";
    $x .= $e->getMessage()."\n";
    Yii::app()->user->setFlash('error', $x);
    //Yii::app()->controller->redirect("index.php?r=orderHeader/admin");  
}
}


public function sendInvoiceOverMail($pdfArray){


}


public static function checkCreditLimit($retailerId){
    $retailer = Retailer::model()->findByPk($retailerId);
    if($retailer->total_payable_amount >= $retailer->credit_limit){
     return true;
 }
 else return true ;
}

public function getEffectivePrice($r_id, $d_date){
    $indexedResult = array();
    $connection = Yii::app()->secondaryDb;
    $sql = "select sp.base_product_id,  rpq.effective_price from cb_dev_groots.retailer_product_quotation_log as rpq left join cb_dev_groots.subscribed_product as sp on rpq.subscribed_product_id = sp.subscribed_product_id where rpq.retailer_id = ".$r_id." and (rpq.date <= '".$d_date."' or rpq.date is null) and rpq.status = 1 order by rpq.date desc, id desc";
    $command = $connection->createCommand($sql);
    $command->execute();
    $result = $command->queryAll();
    foreach ($result as $key => $cur_entry) {
        if(!isset($indexedResult[$cur_entry['base_product_id']])){
            $indexedResult[$cur_entry['base_product_id']] = $cur_entry['effective_price'];
        }

    }
    return $indexedResult;
}


public function actionSendMailToRetailerWithOrderId($orderId){
    $pdf = $this->actionReport($orderId, 'invoice', true);
    $pdfArray = array();
    $pdfArray[0]['pdf'] = $pdf;
    $pdfArray[0]['order_id'] = $orderId;
    try{
        self::sendMailToRetailer($pdfArray, false);
        $result['status'] = 1;
        $result['msg'] = 'Email sent Successfully'; 
        echo json_encode($result);  
    }catch (Exception $e){
        $result['status'] = 1;
        $result['msg'] = 'Retailer Email Failed';
        echo json_encode($result);
    }
}

public function actionMailConfirmedOrders(){
    //echo '<pre>';
    //var_dump($_GET);die;
    $date = '';
    $model = new OrderLine('search');
    $model->unsetAttributes();
    if (isset($_GET['OrderLine'])) {
        $model->attributes = $_GET['OrderLine'];
    }
    if(isset($_POST['date'])){
        $date = $_POST['date'];

    }
    else $date = date('Y-m-d');
    $parentIds = $titles = array();
    if(isset($_POST['base_product_id'])){
        foreach($_POST['base_product_id'] as $key => $id){
            if(isset($_POST['checkedId_'.$id])){
                array_push($parentIds, $id);
                array_push($titles, $_POST['title'][$key]);
            }
        }
        $orderData = OrderHeaderDao::getuserIdsFromShortSku($parentIds,$date);
        self::sendMailShortSkus($orderData,$titles);
        //var_dump($orderData);die;
    }

    $this->render('mailConfirmedOrders',
        array('date' => $date,
            //'dataProvider' => $dataProvider,
            'model' => $model));
}

public function sendMailShortSkus($orderData, $titles){
    $pdfArray = array();
    //$titles= implode(', ',$titles);
    foreach ($orderData as $orderId){
        array_push($pdfArray, array('order_id'=>$orderId));
    }
    $skuShort = array();
    $skuShort['isShort'] = true;
    $skuShort['message'] = "Following Items are short in today's delivery".'<br>';
    foreach ($titles as $count => $title){
        $key = $count+1;
        $skuShort['message'] .= $key.' '.$title.'<br>';
    }
    $skuShort['message'] .= 'Sorry For Inconvinience';
    try{
        self::sendMailToRetailer($pdfArray, $skuShort);
        Yii::app()->user->setFlash('success','Email Sent Successfully' );
    } catch(Exception $e){
        Yii::app()->user->setFlash('error','Email Could Not Be Sent '.$e->getMessage() );
    }
}
}