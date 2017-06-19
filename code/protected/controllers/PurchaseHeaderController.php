<?php

class PurchaseHeaderController extends Controller
{
    /**
     * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
     * using two-column layout. See 'protected/views/layouts/column2.php'.
     */
    public $layout = '//layouts/column2';

    /**
     * @return array action filters
     */
    public function filters()
    {
        return array(
            'accessControl', // perform access control for CRUD operations
            'postOnly + delete', // we only allow deletion via POST request
        );
    }

    /**
     * Specifies the access control rules.
     * This method is used by the 'accessControl' filter.
     * @return array access control rules
     */
    public function accessRules()
    {
        return array(
            array('allow',  // allow all users to perform 'index' and 'view' actions
                'actions' => array('index', 'view'),
                'users' => array('*'),
            ),
            array('allow', // allow authenticated user to perform 'create' and 'update' actions
                'actions' => array('create', 'update', 'admin', 'downloadReconciliationReport', 'dailyProcurement', 'downloadProcurementReport', 'DownloadReportById', 'bulkUploadPurchase', 'downloadPurchaseTemplate','intervalPurchaseReport'),
                'users' => array('@'),
            ),
            array('allow', // allow admin user to perform 'admin' and 'delete' actions
                'actions' => array('admin', 'delete', 'DownloadReportById', 'bulkUploadPurchase', 'downloadPurchaseTemplate'),
                'users' => array('admin'),
            ),
            array('deny',  // deny all users
                'users' => array('*'),
            ),
        );
    }

    /*protected function beforeAction() {
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

    protected function beforeAction()
    {
        $w_id = '';
        if (parent::beforeAction()) {
            if (isset($_GET['w_id'])) {
                $w_id = $_GET['w_id'];
            }
            if ($w_id > 0 && $this->checkAccessByData('PurchaseViewer', array('warehouse_id' => $w_id))) {
                return true;
            }
            if ($w_id > 0 && $this->checkAccessByData('ProcurementViewer', array('warehouse_id' => $w_id))) {
                return true;
            } elseif ($this->checkAccess('SuperAdmin')) {
                return true;
            } else {
                Yii::app()->user->setFlash('permission_error', 'You have no permission to access this page');
                Yii::app()->controller->redirect("index.php?r=user/profile");
            }
        }
    }


    /**
     * Displays a particular model.
     * @param integer $id the ID of the model to be displayed
     */
    public function actionView($id)
    {
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
        //var_dump($_POST);die;
        //echo "<pre>";
        $w_id = '';
        if (isset($_GET['w_id'])) {
            $w_id = $_GET['w_id'];
        }

        if (!$this->checkAccessByData('ProcurementEditor', array('warehouse_id' => $w_id))) {
            Yii::app()->user->setFlash('premission_info', 'You dont have permission.');
            Yii::app()->controller->redirect("index.php?r=purchaseHeader/admin&w_id=" . $w_id);
        }

        $model = new PurchaseHeader('search');
        /*list($popularItems, $otherItems) = BaseProduct::PopularItems();
        $dataProvider=new CArrayDataProvider($popularItems, array(
            'pagination'=>array(
                'pageSize'=>300,
            ),
        ));*/

        $purchaseLineMap = array();
        $inv_header = new InventoryHeader('search');

        $inv_header->warehouse_id = $w_id;
        //$model->warehouse_id = $w_id;
        if (isset($_GET['InventoryHeader'])) {
            $inv_header->attributes = $_GET['InventoryHeader'];
        }

        $dataProvider = $inv_header->search();
//print_r($_POST);die;
        //print_r($otherItems);die;

        // Uncomment the following line if AJAX validation is needed
        // $this->performAjaxValidation($model);

        if (isset($_POST['purchase-create'])) {
            $transaction = Yii::app()->secondaryDb->beginTransaction();
            try {
                $parentIdArr = array();
                $parentIdToUpdate = '';
                if (isset($_POST['parent_id'][0]) && $_POST['parent_id'][0] >= 0) {
                    array_push($parentIdArr, $_POST['parent_id'][0]);
                }

                $model->attributes = $_POST['PurchaseHeader'];
                $model->created_at = date('Y-m-d');
                if ($model->payment_method == '') {
                    $model->payment_method = null;
                }
                if ($model->payment_status == '') {
                    $model->payment_status = null;
                }
                if ($model->paid_amount == '') {
                    $model->paid_amount = null;
                }
                if ($model->comment == '') {
                    $model->comment = null;
                }
                //print_r($model);die;

                if ($model->save()) {

                    if (isset($_POST['order_qty']) || isset($_POST['received_qty'])) {
                        foreach ($_POST['base_product_id'] as $key => $id) {

                            $quantity = '';
                            if (isset($_POST['order_qty'][$key])) {
                                $quantity = $_POST['order_qty'][$key];
                            }
                            $receivedQty = '';
                            if (isset($_POST['received_qty'][$key])) {
                                $receivedQty = $_POST['received_qty'][$key];
                            }

                            if ($quantity > 0 || $receivedQty > 0) {
                                $unitPrice = $_POST['price'][$key];
                                $totalPrice = $_POST['totalPrice'][$key];
                                $vendorId = $_POST['vendorId'][$key];
                                $urd_number = isset(trim($_POST['urd_number'][$key])) ? trim($_POST['urd_number'][$key]) : 0;
                                $isParent = ($_POST['parent_id'][$key] == 0) ? true : false;
                                $flag = PurchaseHeader::validatePriceVendorInput($unitPrice, $totalPrice, $vendorId, $isParent);
                                if ($flag['status'] == 1) {
                                    $purchaseLine = new PurchaseLine();
                                    $purchaseLine->purchase_id = $model->id;
                                    $purchaseLine->base_product_id = $id;
                                    if ($quantity > 0) {
                                        $purchaseLine->order_qty = $quantity;
                                    }
                                    if ($receivedQty > 0) {
                                        $purchaseLine->received_qty = $receivedQty;
                                    }
                                    $purchaseLine->unit_price = $unitPrice;
                                    $purchaseLine->price = $totalPrice;
                                    $purchaseLine->created_at = date("y-m-d H:i:s");
                                    $purchaseLine->vendor_id = $vendorId;
                                    $purchaseLine->urd_number = $urd_number;

                                    if (!$purchaseLine->save()) {
                                        die(print_r($purchaseLine->getErrors()));
                                    }
                                } else {
                                    $transaction->rollBack();
                                    Yii::app()->user->setFlash('error', $flag['msg'] . ' For Product Id' . $id);
                                    $this->redirect(array('create', 'w_id' => $w_id));
                                }

                            }

                            $parentIdToUpdate = $_POST['parent_id'][$key];
                        }
                    }

                    if ($parentIdToUpdate != '' && $parentIdToUpdate > 0) {
                        array_push($parentIdArr, $parentIdToUpdate);
                    }
                    $transaction->commit();
                    $this->updateParentsItems($parentIdArr, $model->id);
                    $this->redirect(array('admin', 'w_id' => $model->warehouse_id));
                } else {
                    Yii::app()->user->setFlash('error', 'Purchase order Creation failed.');
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::app()->user->setFlash('error', 'Purchase order Creation failed.');
                throw $e;
            }

        }
        $priceMap = VendorDao::getAllVendorsPriceMap();
        $this->render('create', array(
            'model' => $model,
            'inv_header' => $inv_header,
            'purchaseLineMap' => $purchaseLineMap,
            'dataProvider' => $dataProvider,
            //'otherItems'=> $otherItems,
            'w_id' => $w_id,
            'priceMap' => $priceMap,
        ));
    }


    /**
     * Updates a particular model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id the ID of the model to be updated
     */
    public function actionUpdate($id)
    {
        //echo "<pre>";
        //var_dump($_POST);die;
        $user = '';
        $w_id = '';
        if (isset($_GET['w_id'])) {
            $w_id = $_GET['w_id'];
        }
        if(($this->checkAccessByData(array('ProcurementEditor', 'PurchaseEditor'), array('warehouse_id' => $w_id)))){
            $user = 'ProcurementEditor';
        }
        if($this->checkAccessByData('PurchaseEditor', array('warehouse_id' => $w_id))){
            $user = 'PurchaseEditor';
        }
        if($this->checkAccessByData('SuperAdmin', array('warehouse_id' => $w_id))){
            $user = 'SuperAdmin';
        }
        $model = $this->loadModel($id);
        /*if(!($this->checkAccess('ProcurementEditor', array('warehouse_id'=>$w_id)) || $this->checkAccess('PurchaseEditor',array('warehouse_id'=>$w_id)))){
            Yii::app()->controller->redirect("index.php?r=purchaseHeader/admin&w_id=".$w_id);
        }*/
        // only procurementEditor and PurchaseEditor and their Parents can update Purchases
        if (!($this->checkAccessByData(array('ProcurementEditor', 'PurchaseEditor'), array('warehouse_id' => $w_id)))) {
            Yii::app()->controller->redirect("index.php?r=purchaseHeader/admin&w_id=" . $w_id);
        }//if status went to delivered then all received_qty feilds must be set
        elseif ($this->checkAccessByData('PurchaseEditor', array('warehouse_id' => $w_id))) {
            if (isset($_POST) && !empty($_POST) && $_POST['PurchaseHeader']['status'] == 'received' && $model->status != 'received') {
                $isDataPerfect = PurchaseHeader::validateReceviedData($_POST);
                if (!$isDataPerfect['status']) {
                    Yii::app()->user->setFlash('error', $isDataPerfect['msg']);
                    Yii::app()->controller->redirect("index.php?r=purchaseHeader/admin&w_id=" . $w_id);
                }
            }

        }//if status is already Delivered then procurementEditor cannot update
        elseif (!($this->checkAccessByData('SuperAdmin', array('warehouse_id' => $w_id))) && $this->checkAccessByData('ProcurementEditor', array('warehouse_id' => $w_id))) {
            if ($model->status == 'received') {
                Yii::app()->user->setFlash('error', 'Purchase Status is already Delivered. Please Contact Admin');
                Yii::app()->controller->redirect("index.php?r=purchaseHeader/admin&w_id=" . $w_id);
            }
        }
        $updateType = "update";
        if (isset($_GET['type'])) {
            if ($_GET['type'] == "add") {
                $updateType = "add";
            }
        }


        $inv_header = new InventoryHeader('search');
        $inv_header->warehouse_id = $w_id;

        if (isset($_GET['InventoryHeader'])) {
            $inv_header->attributes = $_GET['InventoryHeader'];
        }
        $inv_header->purchase_id = $id;
        $inv_header->update_type = $updateType;
        $dataProvider = $inv_header->purchaseSearch();
        if (isset($_POST['purchase-update'])) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $prodIds = implode(',',$_POST['base_product_id']);
                $purchaseLines = PurchaseLine::model()->findAllByAttributes(array('purchase_id' => $id), array('condition'=> 'base_product_id in ('.$prodIds.')'));
                $purchaseLineMap = array();
                foreach ($purchaseLines as $item) {
                    $constraint = $item->base_product_id . '~' . $item->vendor_id;
                    $purchaseLineMap[$constraint] = $item;
                }
                //var_dump($purchaseLineMap);
                $model->attributes = $_POST['PurchaseHeader'];
                $parentIdArr = array();
                $parentIdToUpdate = '';
                if (isset($_POST['parent_id'][0]) && $_POST['parent_id'][0] >= 0) {
                    array_push($parentIdArr, $_POST['parent_id'][0]);
                }

                if ($model->payment_method == '') {
                    $model->payment_method = null;
                }
                if ($model->payment_status == '') {
                    $model->payment_status = null;
                }
                if ($model->paid_amount == '') {
                    $model->paid_amount = null;
                }
                if ($model->comment == '') {
                    $model->comment = null;
                }
                if ($model->save()) {
                    if (isset($_POST['order_qty']) || isset($_POST['received_qty'])) {


                        foreach ($_POST['base_product_id'] as $key => $id) {
                            $order_qty = $received_qty = $unitPrice = $totalPrice = 0.00;
                            $vendorId = $urd_number = 0;
                            if (isset($_POST['order_qty'][$key]) && !empty($_POST['order_qty'][$key])) {
                                $order_qty = trim($_POST['order_qty'][$key]);
                            }

                            if (isset($_POST['received_qty'][$key]) && !empty($_POST['received_qty'][$key])) {
                                $received_qty = trim($_POST['received_qty'][$key]);

                            }
                            if (isset($_POST['vendorId'][$key]) && !empty($_POST['vendorId'][$key])) {
                                $vendorId = trim($_POST['vendorId'][$key]);
                            }
                            //var_dump($vendorId);
                            $constraint = $id . '~' . $vendorId;
                            $purchaseLine = new PurchaseLine();
                            if (isset($purchaseLineMap[$constraint])) {
                                $purchaseLine = $purchaseLineMap[$constraint];
                            }
                            
                            if ($order_qty > 0.00 && $user != 'PurchaseEditor') {
                                //die('here');
                                $unitPrice = trim($_POST['price'][$key]);
                                $totalPrice = trim($_POST['totalPrice'][$key]);
                                $urd_number = isset(trim($_POST['urd_number'][$key])) ? trim($_POST['urd_number'][$key]) : 0;
                                $isParent = ($_POST['parent_id'][$key] == 0) ? true : false;
                                $flag = PurchaseHeader::validatePriceVendorInput($unitPrice, $totalPrice, $vendorId, $isParent);
                                if ($flag['status'] == 1) {
                                    $purchaseLine->purchase_id = $model->id;
                                    $purchaseLine->base_product_id = $id;
                                    $purchaseLine->created_at = date("y-m-d H:i:s");
                                    $purchaseLine->order_qty = $order_qty;
                                    $purchaseLine->received_qty = $received_qty;
                                    $purchaseLine->vendor_id = $vendorId;
                                    $purchaseLine->unit_price = $unitPrice;
                                    $purchaseLine->price = $totalPrice;
                                    $purchaseLine->urd_number = $urd_number;
                                    $purchaseLine->save();
                                    if(isset($purchaseLineMap[$constraint])){
                                        unset($purchaseLineMap[$constraint]);
                                    }
                                } else {
                                    $transaction->rollBack();
                                    Yii::app()->user->setFlash('error', $flag['msg'] . ' For Product Id' . $id);
                                    $this->redirect(array('update', "w_id" => $w_id, "id" => $model->id, 'type' => $updateType));
                                }

                            }
                            if (!empty($_POST['received_qty'][$key])  || $received_qty > 0.00 ) {
                                //$purchaseLine->purchase_id = $model->id;
                                $purchaseLine->received_qty = $received_qty;
                                $purchaseLine->save();
                                if(isset($purchaseLineMap[$constraint])){
                                    unset($purchaseLineMap[$constraint]);
                                }
                            }
                            else if (isset($purchaseLineMap[$constraint]) && (isset($_POST['order_qty'][$key]) && empty($_POST['order_qty'][$key]))) {
                                $purchaseLine = $purchaseLineMap[$constraint];
                                unset($purchaseLineMap[$constraint]);
                                $purchaseLine->deleteByPk($purchaseLine->id);
                            }

                            $parentIdToUpdate = $_POST['parent_id'][$key];
                        }
                        //die('here');
                    }
                    foreach ($purchaseLineMap as $p_line){
                        $p_line->deleteByPk($p_line->id);
                    }

                    $transaction->commit();
                    if ($parentIdToUpdate != '' && $parentIdToUpdate > 0) {
                        array_push($parentIdArr, $parentIdToUpdate);
                    }
                    $this->updateParentsItems($parentIdArr, $model->id);
                    $url = Yii::app()->controller->createUrl("purchaseHeader/update", array("w_id" => $w_id, "id" => $model->id, 'type' => $updateType));
                    Yii::app()->user->setFlash('success', 'purchase order successfully Updated.');
                    Yii::app()->controller->redirect($url);
                    //$this->redirect(array('admin','w_id'=>$model->warehouse_id));
                } else {
                    Yii::app()->user->setFlash('error', 'Purchase order Update failed.');
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::app()->user->setFlash('error', 'Purchase order Creation failed.');
                throw $e;
            }
        }

        $priceMap = VendorDao::getAllVendorsPriceMap();
        $this->render('update', array(
            'model' => $model,
            'inv_header' => $inv_header,
            //'purchaseLineMap'=> $purchaseLineMap,
            'dataProvider' => $dataProvider,
            //'otherItems'=> $otherItems,
            'w_id' => $_GET['w_id'],
            'update' => true,
            'priceMap' => $priceMap,
        ));
    }


    private function updateParentsItems($parentIdArr, $purchaseId)
    {
        foreach ($parentIdArr as $parentId) {
            if ($parentId > 0) {
                $orderQty = 0;
                $receivedQty = 0;
                $tobe_procured_qty = 0;
                $childIds = BaseProduct::getChildBPIds($parentId);
                foreach ($childIds as $bp_id) {
                    $pl = PurchaseLine::model()->findByAttributes(array('base_product_id' => $bp_id, 'purchase_id' => $purchaseId));
                    if ($pl) {
                        $orderQty += $pl->order_qty;
                        $receivedQty += $pl->received_qty;
                        $tobe_procured_qty += $pl->tobe_procured_qty;
                    }

                }
                $parentPl = PurchaseLine::model()->findByAttributes(array('base_product_id' => $parentId, 'purchase_id' => $purchaseId));
                if ($parentPl == false && ($orderQty > 0 || $receivedQty > 0 || $tobe_procured_qty > 0)) {
                    $parentPl = new PurchaseLine();
                    $parentPl->purchase_id = $purchaseId;
                    $parentPl->base_product_id = $parentId;
                    $parentPl->created_at = date('Y-m-d');
                }
                if ($parentPl) {
                    $parentPl->order_qty = $orderQty;
                    $parentPl->received_qty = $receivedQty;
                    $parentPl->tobe_procured_qty = $tobe_procured_qty;
                    $parentPl->save();
                }

            }
        }
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDelete($id)
    {
        $this->loadModel($id)->delete();

        // if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
        if (!isset($_GET['ajax']))
            $this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
    }

    /**
     * Lists all models.
     */
    public function actionIndex()
    {
        $dataProvider = new CActiveDataProvider('PurchaseHeader');
        $this->render('index', array(
            'dataProvider' => $dataProvider,
        ));
    }

    /**
     * Manages all models.
     */
    public function actionAdmin()
    {
        $model = new PurchaseHeader('search');
        $model->unsetAttributes();  // clear any default values
        $w_id = $_GET['w_id'];
        if (isset($_GET['PurchaseHeader']))
            $model->attributes = $_GET['PurchaseHeader'];
        $model->warehouse_id = $w_id;
        $showCreate = false;
        if ($w_id > 0 && $this->checkAccessByData('ProcurementViewer', array('warehouse_id' => $w_id))) {
            $showCreate = true;
        }
        $this->render('admin', array(
            'model' => $model,
            'w_id' => $w_id,
            'showCreate' => $showCreate,
        ));
    }


    public function actionDownloadReconciliationReport()
    {
        $w_id = $_GET['w_id'];
        $date = $_GET['date'];
        $sql = 'select pl.base_product_id, bp.title, ph.id, ph.delivery_date, pl.order_qty as order_qty, pl.received_qty as received_qty from groots_orders.purchase_line as pl
                left join groots_orders.purchase_header as ph
                on ph.id = pl.purchase_id
                left join cb_dev_groots.base_product as bp 
                on bp.base_product_id = pl.base_product_id
                left join cb_dev_groots.product_category_mapping pcm on pcm.base_product_id=bp.base_product_id
                where ( pl.order_qty != pl.received_qty or pl.order_qty is null or pl.received_qty is null) and ph.delivery_date = ' . "'" . $date . "'" . 'and ph.warehouse_id = ' . $w_id . ' and ph.status in ("received" , "pending")
                 order by pcm.category_id asc, bp.base_title asc, bp.priority asc ';
        $connection = Yii::app()->secondaryDb;
        $command = $connection->createCommand($sql);
        $command->execute();
        $dataArray = $command->queryAll();
        if (!isset($dataArray) || empty($dataArray)) {
            Yii::app()->user->setFlash('error', 'nothing to download... select correct date!!!');
            Yii::app()->controller->redirect("index.php?r=purchaseHeader/admin&w_id=" . $w_id);
        } else {
            $w_name = str_replace(' ', '', Utility::getWarehouseNameById($w_id));
            $fileName = $date . "reconciliation_report" . ".csv";
            ob_clean();
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: private', false);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename=' . $fileName);

            if (isset($dataArray['0'])) {
                $fp = fopen('php://output', 'w');
                $columnstring = implode(',', array_keys($dataArray['0']));
                $updatecolumn = str_replace('_', ' ', $columnstring);

                $updatecolumn = explode(',', $updatecolumn);
                fputcsv($fp, $updatecolumn);
                foreach ($dataArray AS $values) {
                    fputcsv($fp, $values);
                }
                fclose($fp);
            }
            ob_flush();
        }
    }


    public static function createProcurementOrder($purchaseOrderMap, $date, $w_id)
    {
        //echo "<pre>";
        //print_r($purchaseOrderMap);
        $purchaseOrder = PurchaseHeader::model()->findByAttributes(array('delivery_date' => $date, 'warehouse_id' => $w_id, 'purchase_type' => 'regular', 'status' => 'pending'));


        $transaction = Yii::app()->db->beginTransaction();
        try {

            if (empty($purchaseOrder)) {
                $purchaseOrder = new PurchaseHeader();
                $purchaseOrder->warehouse_id = $w_id;
                // $purchaseOrder->vendor_id = 1;
                $purchaseOrder->delivery_date = $date;
                $purchaseOrder->status = 'pending';
                $purchaseOrder->comment = 'system generated';
                $purchaseOrder->created_at = date('Y-m-d');
                $purchaseOrder->purchase_type = "regular";
            }
            $purchaseOrder->save();
            $purchaseLineMap = self::getPurchaseLineMap($purchaseOrder->id);
            foreach ($purchaseOrderMap as $bp_id => $qty) {
                if ($qty < 0) {
                    $qty = 0;
                }
                if (isset($purchaseLineMap[$bp_id])) {
                    $item = $purchaseLineMap[$bp_id];

                    $item->tobe_procured_qty = $qty;
                    $item->save();
                } else {
                    $item = new PurchaseLine();
                    $item->purchase_id = $purchaseOrder->id;
                    $item->base_product_id = $bp_id;
                    $item->status = 'pending';
                    $item->created_at = date('Y-m-d');
                    $item->tobe_procured_qty = $qty;
                    $item->vendor_id = 0;
                    $item->save();
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::app()->user->setFlash('error', 'Transfer order Creation failed.');
            throw $e;
            Yii::app()->controller->redirect(Yii::app()->request->urlReferrer);
        }
    }

    private static function getPurchaseLineMap($purchase_id)
    {
        $itemArr = array();
        $items = PurchaseLine::model()->findAllByAttributes(array('purchase_id' => $purchase_id));
        foreach ($items as $item) {
            $itemArr[$item->base_product_id] = $item;
        }
        return $itemArr;
    }

    public function actionDailyProcurement()
    {
        //echo "<pre>";
        if (empty($_GET['w_id'])) {
            Yii::app()->controller->redirect("index.php?r=user/profile");
        }
        $w_id = $_GET['w_id'];

        if (empty($_POST['delivery_date'])) {
            //Yii::app()->controller->redirect(Yii::app()->request->urlReferrer);
            $this->redirect(Yii::app()->request->urlReferrer);
        }
        $date = $_POST['delivery_date'];
        //$date = $this->getDateForDailyTransfer();

        TransferDao::createTransfer($w_id, $date);

        Yii::app()->controller->redirect(Yii::app()->request->urlReferrer);
    }


    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return PurchaseHeader the loaded model
     * @throws CHttpException
     */
    public function loadModel($id)
    {
        $model = PurchaseHeader::model()->findByPk($id);
        if ($model === null)
            throw new CHttpException(404, 'The requested page does not exist.');
        return $model;
    }

    /**
     * Performs the AJAX validation.
     * @param PurchaseHeader $model the model to be validated
     */
    protected function performAjaxValidation($model)
    {
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'purchase-header-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }
    }

    public function actionDownloadProcurementReport()
    {
        //echo "<pre>";
        $w_id = $_GET['w_id'];
        $sourceWId = SOURCE_WH_ID;
        $headOffId = HD_OFFICE_WH_ID;
        if ($w_id != $sourceWId) {
            return;
        }
        $date = $_GET['date'];
        $data = array();
        $sql = 'select pl.base_product_id, bp.title as title, bp.grade as grade, wa.name as warehouse, sum(pl.tobe_procured_qty) as tobe_procured_qty, sum(pl.order_qty) as procured_qty, sum(pl.received_qty) as received_qty, pl.unit_price, pl.price as total_price from groots_orders.purchase_line as pl 
        left join purchase_header as ph on ph.id = pl.purchase_id left join cb_dev_groots.base_product as bp on pl.base_product_id = bp.base_product_id left join cb_dev_groots.warehouses as wa on ph.warehouse_id = wa.id
        left join cb_dev_groots.product_category_mapping pcm on pcm.base_product_id=bp.base_product_id
         where ph.status not in ("failed", "cancelled") and ph.delivery_date = ' . '"' . $date . '"' . 'and ph.warehouse_id = ' . $w_id . ' group by pl.base_product_id order by pcm.category_id asc, bp.base_title asc, bp.priority asc ';
        $connection = Yii::app()->secondaryDb;
        $command = $connection->createCommand($sql);
        $command->execute();
        $dataArray = $command->queryAll();
        if (!isset($dataArray) || empty($dataArray)) {
            Yii::app()->user->setFlash('error', 'nothing to download...');
            Yii::app()->controller->redirect("index.php?r=purchaseHeader/admin&w_id=" . $w_id);
        }

        $sql = 'select name, id from cb_dev_groots.warehouses order by id';
        $command = $connection->createCommand($sql);
        $command->execute();
        $res = $command->queryAll();
        $warehouses = array();
        $nameArr = array();
        foreach ($res as $key => $wh) {
            $w_name = "For " . explode(',', $wh['name'])[0];
            $warehouses[$wh['id']] = $w_name;
            if ($wh['id'] != $headOffId) {
                array_push($nameArr, $w_name);
            }
        }

        foreach ($dataArray as $d) {
            $tmp = array();
            //$tmp['item'] = $d['title'];
            $tmp['item'] = $d['title'];
            $tmp['grade'] = $d['grade'];
            $tmp['tobe_procured_qty'] = $d['tobe_procured_qty'];
            $tmp['procured'] = $d['procured_qty'];
            $tmp['received_by_operation'] = $d['received_qty'];
            $tmp['unit price'] = $d['unit_price'];
            $tmp['total Price'] = $d['total_price'];
            foreach ($nameArr as $name) {
                $tmp[$name] = 0;
            }
            $data[$d['base_product_id']] = $tmp;
        }

        foreach ($warehouses as $wh_id => $name) {

            if ($wh_id == HD_OFFICE_WH_ID) {
                continue;
            }
            $sql = 'select tl.base_product_id, tl.order_qty from groots_orders.transfer_header as th
            join groots_orders.transfer_line as tl
            on th.id = tl.transfer_id
            join cb_dev_groots.base_product as bp
            on bp.base_product_id = tl.base_product_id
            join cb_dev_groots.warehouses sw on sw.id = th.source_warehouse_id
            join cb_dev_groots.warehouses dw on dw.id = th.dest_warehouse_id
            where  th.delivery_date = "' . $date . '" and th.status != "cancelled" and th.source_warehouse_id=' . $sourceWId . ' and th.dest_warehouse_id=' . $wh_id . ' and th.transfer_type = "regular"';
            $command = $connection->createCommand($sql);
            $command->execute();
            $transfers = $command->queryAll();
            foreach ($transfers as $t) {
                if (isset($data[$t['base_product_id']])) {
                    $tmp = $data[$t['base_product_id']];

                    $tmp[$name] = $t['order_qty'];
                    $data[$t['base_product_id']] = $tmp;
                }
            }
        }
        //print_r(reset($data));die;
        //print_r($data);die;

        $fileName = $date . "procurement_report" . ".csv";
        ob_clean();
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $fileName);

        if (count($data) > 0) {
            $fp = fopen('php://output', 'w');
            //$columnstring = implode(',', array_keys($data[0]));
            $columnstring = implode(',', array_keys(reset($data)));

            $updatecolumn = str_replace('_', ' ', $columnstring);

            $updatecolumn = explode(',', $updatecolumn);
            //print_r( $updatecolumn); die;
            fputcsv($fp, $updatecolumn);
            foreach ($data AS $values) {
                fputcsv($fp, $values);
            }

            fclose($fp);
        }
        ob_flush();
    }


    public function actionDownloadReportById($id)
    {
        $sql = 'select ph.delivery_date , pl.*,ca.category_name, bp.title,bp.grade, case when pl.vendor_id = 0 then "" when pl.vendor_id != 0 then v.bussiness_name end as vendorBussinessName from purchase_line pl  left join cb_dev_groots.vendors as v on v.id = pl.vendor_id 
        left join cb_dev_groots.base_product as bp on pl.base_product_id = bp.base_product_id
        left join cb_dev_groots.product_category_mapping pcm on pcm.base_product_id=bp.base_product_id
        left join groots_orders.purchase_header ph on ph.id = pl.purchase_id
        left join cb_dev_groots.category as ca on ca.category_id = pcm.category_id
         where pl.purchase_id = ' . $id . ' order by pcm.category_id asc, bp.base_title asc, bp.priority asc';
        $connection = Yii::app()->secondaryDb;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $data = array();
        foreach ($result as $key => $value) {
            $tmp = array();
            $tmp['date'] = $value['delivery_date'];
            $tmp['product_id'] = $value['base_product_id'];
            $tmp['category_name'] = $value['category_name'];
            $tmp['title'] = $value['title'];
            $tmp['grade'] = $value['grade'];
            $tmp['urd_number'] = $value['urd_number'];
            $tmp['procured qty'] = $value['order_qty'];
            $tmp['received by operations'] = $value['received_qty'];
            $tmp['unit price'] = $value['unit_price'];
            $tmp['total price'] = $value['price'];
            $tmp['vendor'] = $value['vendorBussinessName'];
            array_push($data, $tmp);
        }
        $fileName = $id . "procurement_report_by_id" . ".csv";
        ob_clean();
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $fileName);

        if (count($data) > 0) {
            $fp = fopen('php://output', 'w');
            //$columnstring = implode(',', array_keys($data[0]));
            $columnstring = implode(',', array_keys(reset($data)));

            $updatecolumn = str_replace('_', ' ', $columnstring);

            $updatecolumn = explode(',', $updatecolumn);
            //print_r( $updatecolumn); die;
            fputcsv($fp, $updatecolumn);
            foreach ($data AS $values) {
                fputcsv($fp, $values);
            }

            fclose($fp);
        }
        ob_flush();

    }


    public function actionBulkUploadPurchase()
    {
        //var_dump($_POST);die;
        $w_id = Yii::app()->session['w_id'];
        $logTemplate = array('id', 'base_product_id', 'action', 'status', 'error');
        set_time_limit(0);
        $logfile = '';
        $baseid = '';
        $model = new Bulk();
        $csv_filename = '';
        $cateogryarray = array();

        try {
            if (isset($_POST['Bulk'])) {
                $model->action = 'update';
                $model->attributes = $_POST['Bulk'];
                if (!empty($_FILES['Bulk']['tmp_name']['csv_file'])) {
                    $csv = CUploadedFile::getInstance($model, 'csv_file');
                    if (!empty($csv)) {
                        if ($csv->size > 30 * 1024 * 1024) {
                            Yii::app()->user->setFlash('error', 'Cannot upload file greater than 30 MB.');
                            $this->render('bulkUploadPurchase', array('model' => $model));
                        }
                        $fileName = 'csvupload/' . $csv->name;
                        $filenameArr = explode('.', $fileName);
                        $fileName = $filenameArr[0] . '-' . Yii::app()->session['sessionId'] . '-' . time() . '.' . end($filenameArr);
                        $csv->saveAs($fileName);
                    } else {

                        Yii::app()->user->setFlash('error', 'Please browse a CSV file to upload.');
                        $this->render('bulkUploadPurchase', array('model' => $model));
                    }
                    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                    if ($ext != 'csv') {
                        Yii::app()->user->setFlash('error', 'Only .csv files allowed.');
                        $this->render('bulkUploadPurchase', array('model' => $model));
                    }
                    $csv_filename = LOG_BASE_PDT_DIR . uniqid() . '.csv';
                    $logfile = fopen($csv_filename, "a");
                    $uploadedFile = fopen($fileName, 'r');
                    fputcsv($logfile, $logTemplate);
                    PurchaseHeader::readInventoryUploadedFile($uploadedFile, $logfile, $w_id);
                    Yii::app()->user->setFlash('success', 'File Uploaded Sucessfully.');
                    fclose($logfile);
                }
            }
        } catch (Exception $e) {
            Yii::app()->user->setFlash('error', 'File Upload Failed ' . $e->getMessage());
        }


        // @unlink($fileName);
        $this->render('bulkUploadPurchase', array('model' => $model));
    }

    public function actionDownloadPurchaseTemplate()
    {
        $w_id = $_GET['w_id'];
        $date = $_GET['date'];
        $sql = 'select ph.id as purchase_id,pl.id,vpm.base_product_id,bp.parent_id, bp.title ,v.name,vpm.vendor_id,"' . $date . '" as date, pl.order_qty,pl.price,pl.urd_number from cb_dev_groots.vendor_product_mapping as vpm 
        inner join groots_orders.purchase_header as ph on ph.delivery_date = "' . $date . '"
        left join groots_orders.purchase_line as pl on pl.purchase_id = ph.id and pl.base_product_id = vpm.base_product_id and pl.vendor_id = vpm.vendor_id
        inner join cb_dev_groots.base_product bp on bp.base_product_id = vpm.base_product_id 
        inner join cb_dev_groots.vendors as v on vpm.vendor_id = v.id
        where v.allocated_warehouse_id = ' . $w_id . ' and (bp.grade is null or bp.grade = "Unsorted") and (bp.parent_id is null or bp.parent_id != 0) order by ph.id, bp.title';
        //die($sql);
        $connection = Yii::app()->secondaryDb;
        $command = $connection->createCommand($sql);
        $data = $command->queryAll();
        $fileName = "purchase_template" . $date . ".csv";
        if (count($data) > 0) {
            ob_clean();
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: private', false);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename=' . $fileName);

            $fp = fopen('php://output', 'w');
            //$columnstring = implode(',', array_keys($data[0]));
            $columnstring = implode(',', array_keys(reset($data)));

            $updatecolumn = str_replace('_', ' ', $columnstring);

            $updatecolumn = explode(',', $updatecolumn);
            //print_r( $updatecolumn); die;
            fputcsv($fp, $updatecolumn);
            foreach ($data AS $values) {
                fputcsv($fp, $values);
            }

            fclose($fp);
            ob_flush();
        } else {
            $model = new Bulk();
            Yii::app()->user->setFlash('error', "Can't create purchase");
            $this->render('bulkUploadPurchase', array('model' => $model));
        }
    }


    public function actionIntervalPurchaseReport(){
        $w_id = $_GET['w_id'];
        if(isset($_POST['purchase_from']) && isset($_POST['purchase_to']) && !empty($_POST['purchase_from']) && !empty($_POST['purchase_to'])){
            $fromDate = $_POST['purchase_from'];
            $toDate = $_POST['purchase_to'];
        }
        else{
            Yii::app()->user->setFlash('error', 'Please Select Dates');
            Yii::app()->controller->redirect("index.php?r=purchaseHeader/admin&w_id=".$w_id);
        }
        $connection = Yii::app()->secondaryDb;
        $sql = 'select ph.id,ca.category_name, ph.delivery_date , bp.title , bp.grade, pl.urd_number,pl.order_qty as "procured qty", pl.received_qty, pl.unit_price, pl.price as totalPrice, v.bussiness_name
                from purchase_line pl left join purchase_header ph on pl.purchase_id = ph.id left join cb_dev_groots.base_product bp on bp.base_product_id = pl.base_product_id
                left join cb_dev_groots.vendors as v on v.id = pl.vendor_id
                left join cb_dev_groots.product_category_mapping as pcm on pcm.base_product_id = pl.base_product_id
                left join cb_dev_groots.category as ca on ca.category_id = pcm.category_id
                where ph.warehouse_id = "'.$w_id.'" and ph.delivery_date between "'.$fromDate.'" and "'.$toDate.'"';
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        //var_dump($result);die;
        $fileName = $fromDate.'//'.$toDate.'PurchaseReport.csv';
        if (count($result) > 0) {
            ob_clean();
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Cache-Control: private', false);
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename=' . $fileName);

            $fp = fopen('php://output', 'w');
            //$columnstring = implode(',', array_keys($data[0]));
            $columnstring = implode(',', array_keys(reset($result)));

            $updatecolumn = str_replace('_', ' ', $columnstring);

            $updatecolumn = explode(',', $updatecolumn);
            //print_r( $updatecolumn); die;
            fputcsv($fp, $updatecolumn);
            foreach ($result AS $values) {
                fputcsv($fp, $values);
            }

            fclose($fp);
            ob_flush();
        }

    }


}
