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

    protected function beforeAction() {
        $session = Yii::app()->session['user_id'];
        if ($session == '') {
            echo Yii::app()->controller->redirect("index.php?r=site/logout");
        }

        if (Yii::app()->session['premission_info']['menu_info']['brand_menu_info'] != "S") {
            Yii::app()->user->setFlash('permission_error', 'You have no permission');
            Yii::app()->controller->redirect("index.php?r=DashboardPage/index");
        }

        return true;
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules() {
        return array(
            array('allow', // allow all users to perform 'index' and 'view' actions
                'actions' => array('index', 'sales_by_retailer', 'sales_by_retailer_detail', 'sale_by_style', 'sale_by_style_dateial', 'sale_summery', 'sale_summery_detail', 'view', 'Reportnew', 'admin', 'report', 'Dispatch'),
                'users' => array('*'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('create', 'update', 'admin', 'report', 'Reportnew', 'Dispatch'),
                'users' => array('@'),
            ),
            array('allow', // allow admin user to perform 'admin' and 'delete' actions
                'actions' => array('admin', 'delete', 'admin', 'Reportnew', 'report'),
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
    public function actionCreate() {
        $model = new OrderHeader;

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['OrderHeader'])) {
            $model->attributes = $_POST['OrderHeader'];
            if ($model->save())
                $this->redirect(array('view', 'id' => $model->order_id));
        }

        $this->render('create', array(
            'model' => $model,
        ));
    }

    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id) {
        $model = OrderLine::model()->findAllByAttributes(array('order_id' => $id));
        $modelOrder = $this->loadModel($id);

        $email = $modelOrder->attributes['billing_email'];
       
        

        if (isset($_POST['Update'])) {
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

            if ($status_data[0] == 'Confirmed' || $status_data[0] == 'Cancelled' || $status_data[0] == 'Paid' || $status_data[0] == 'Out for Delivery') {
                //$reportdata = $this->actionReportnew($_REQUEST['order_id'], $status_data[0], $email);
                $modelOrderline = new OrderLine;
                $buyername = $modelOrderline->buyername($modelOrder->attributes['user_id']);
                $from_email = 'grootsadmin@groots.in';
                $from_name = 'Groots Dashboard Admin';
                $subject = 'Groots Buyer Account';
                $urldata = Yii::app()->params['target_app_url'];
                $emailurldata = Yii::app()->params['email_app_url1'];
//                $body_html = 'Hi  <br/> your order id ' . $modelOrder->attributes['order_id'] . ' <br/> status now change<br/><br/></br> ,
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
        <span style="float:right;">+91 99999 99999</span>
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
            Your order (id :- ' . $modelOrder->attributes['order_number'] . ') status has been changed to <strong >  ' . $status_data[0] . '</strong> <br>
           
          </span>
          <br>

        <a href="' . $urldata . '">
             <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
            </a>
        <br> <br> 
      </p>
     </td>          
   </tr>
    <tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
      <td colspan="2" style="width: 600px;">
        <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
          <li style="display:block; width:200px; float:left; text-align:center;">
            <a href="#!" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
          </li>
          <li style="display:block; width:200px; float:left; text-align:center;">
            <a href="#!" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
          </li>
          <li style="display:block; width:200px; float:left; text-align:center;">
            <a href="#!" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
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
                $reportdata = $this->actionReportnew($_REQUEST['order_id'], $status_data[0], $email);
                $csv_name = 'order_' . $modelOrder->attributes['order_id'] . '.pdf';
                $csv_filename = "feeds/order_csv/" . $csv_name;
                $from_email = 'grootsadmin@groots.in';
                $from_name = 'Groots Dashboard Admin';
                $subject = 'Groots Buyer Account';
                $urldata = Yii::app()->params['email_app_url'];
                $emailurldata = Yii::app()->params['email_app_url1'];
//                $body_html = 'Hi  <br/> your order id ' . $modelOrder->attributes['order_id'] . ' <br/> status now change</br>:  ' . $status_data[0] . ',
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
        <span style="float:right;">+91 99999 99999</span>
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
            Your order (id :- ' . $modelOrder->attributes['order_number'] . ') status has been changed to <strong >  ' . $status_data[0] . '</strong> <br>
            <br/> <a href =' . $urldata . $modelOrder->attributes['order_id'] . '_' . md5('Order' . $modelOrder->attributes['order_id']) . '.' . 'pdf' . '> Click here to download invoice </a><br/>
          </span>
          <br>

        <a href="' . $urldata . '">
             <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
            </a>
        <br> <br> 
      </p>
     </td>          
   </tr>
    <tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
      <td colspan="2" style="width: 600px;">
        <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
          <li style="display:block; width:200px; float:left; text-align:center;">
            <a href="#!" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
          </li>
          <li style="display:block; width:200px; float:left; text-align:center;">
            <a href="#!" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
          </li>
          <li style="display:block; width:200px; float:left; text-align:center;">
            <a href="#!" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
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
                        $lineinfo = $linedescinfo->Updatesizequantity($order_line_id, $baseproduct_id, $size_quantity);
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
   
        
        $model = new OrderHeader('search');
        
        if (isset($_GET['pageSize'])) {
            Yii::app()->user->setState('pageSize', (int) $_GET['pageSize']);
            unset($_GET['pageSize']);
        }
        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['OrderHeader']))
            $model->attributes = $_GET['OrderHeader'];
        //echo "eeeee";die;
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
                    if ($_POST['status1'] == 'Confirmed' || $_POST['status1'] == 'Paid' || $_POST['status1'] == 'Cancelled' || $_POST['status1'] == 'Out for Delivery') {
                        //$reportdata = $this->actionReportnew($_POST['selectedIds'][$i], $_POST['status1'], $email);
                        $modelOrderline = new OrderLine;
                        $buyername = $modelOrderline->buyernamegrid($_POST['selectedIds'][$i]);
                        $from_email = 'grootsadmin@groots.in';
                        $from_name = 'Groots Dashboard Admin';
                        $subject = 'Groots Buyer Account';
                        $urldata = Yii::app()->params['target_app_url'];
                        $emailurldata = Yii::app()->params['email_app_url1'];
//                        $body_html = 'Hi  <br/> your order id ' . $_POST['selectedIds'][$i] . ' <br/> status now change</br>
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
        <span style="float:right;">+91 99999 99999</span>
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
            Your order (id :- ' . $order_number . ') status has been changed to <strong >  ' . $_POST['status1'] . '</strong> <br>
           
          </span>
          <br>

        <a href="' . $urldata . '">
             <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
            </a>
        <br> <br> 
      </p>
     </td>          
   </tr>
    <tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
      <td colspan="2" style="width: 600px;">
        <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
          <li style="display:block; width:200px; float:left; text-align:center;">
            <a href="#!" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
          </li>
          <li style="display:block; width:200px; float:left; text-align:center;">
            <a href="#!" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
          </li>
          <li style="display:block; width:200px; float:left; text-align:center;">
            <a href="#!" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
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
                    if ($_POST['status1'] == 'Delivered') {
                        $reportdata = $this->actionReportnew($_POST['selectedIds'][$i], $_POST['status1'], $email);
                        $modelOrderline = new OrderLine;
                        $buyername = $modelOrderline->buyernamegrid($_POST['selectedIds'][$i]);
                        $csv_name = 'order_' . $_POST['selectedIds'][$i] . '.pdf';
                        $csv_filename = "feeds/order_csv/" . $csv_name;
                        $from_email = 'grootsadmin@groots.in';
                        $from_name = 'Groots Dashboard Admin';
                        $subject = 'Groots Buyer Account';
                        $urldata = Yii::app()->params['email_app_url'];
                        $emailurldata = Yii::app()->params['email_app_url1'];
//                        $body_html = 'Hi  <br/> your order id ' . $_POST['selectedIds'][$i] . ' <br/> status now change</br>:  ' . $_POST['status1'] . ',
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
        <span style="float:right;">+91 99999 99999</span>
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
            Your order (id :- ' . $order_number . ') status has been changed to <strong >  ' . $_POST['status1'] . '</strong> <br>
           <br/> <a href =' . $urldata . $_POST['selectedIds'][$i] . '_' . md5('Order' . $_POST['selectedIds'][$i]) . '.' . 'pdf' . '> Click here to download invoice </a><br/>
          </span>
          <br>

        <a href="' . $urldata . '">
             <img src="' . $emailurldata . 'emailimage/android.png" alt="call" width="225" style= text-indent:-2000px; display:block;"> 
            </a>
        <br> <br> 
      </p>
     </td>          
   </tr>
    <tr style="display: block; margin-top:0px;background: #444; padding: 15px 0;">
      <td colspan="2" style="width: 600px;">
        <ul style="display:block; width:100%; list-style-type:none;overflow: hidden;margin: 0;padding: 10px 0;">
          <li style="display:block; width:200px; float:left; text-align:center;">
            <a href="#!" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-right:1px solid #676767;">Visit Website</a>
          </li>
          <li style="display:block; width:200px; float:left; text-align:center;">
            <a href="#!" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px;">Terms &amp; Conditions</a>
          </li>
          <li style="display:block; width:200px; float:left; text-align:center;">
            <a href="#!" style="display:block;color:#a9a9a9; text-transform:uppercase;text-decoration:none; font-size:14px; border-left:1px solid #676767;">Privacy Policy</a>
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
                }
                if ($no_of_selectedIds > 0) {
                    $status_order = $_POST['status1'];
                    $order_ids = implode(',', $_POST['selectedIds']);
                    if ($status_order != 'Change status') {
                        $active_record = $model->StatusOrderByID($order_ids, $status_order);

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

    public function actionReport($id) {
        // echo "hello";die;
        $model = OrderLine::model()->findAllByAttributes(array('order_id' => $id));
        $modelOrder = $this->loadModel($id);
        $this->renderPartial('reportview', array(
            'model' => $model,
            'modelOrder' => $modelOrder,
        ));
    }

    public function actionReportnew($id, $status, $email) {
        //  echo $status;die;
        $model = OrderLine::model()->findAllByAttributes(array('order_id' => $id));
        $modelOrder = $this->loadModel($id);
        $store_model = new Store();
        $this->renderPartial('reportviewdata', array(
            'model' => $model,
            'modelOrder' => $modelOrder,
            'status' => $status,
            'email' => $email,
            'store_model' => $store_model,
        ));
        //$this->renderPartial("reportview");
    }

}
