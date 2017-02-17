<?php

class VendorController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

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
				'actions'=>array('index','view'),
				'users'=>array('*'),
				),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update', 'productMap', 'creditManagement', 'vendorLedger', 'vendorScript','admin','invoice'),
				'users'=>array('@'),
				),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','delete'),
				'users'=>array('admin'),
				),
			array('deny',  // deny all users
				'users'=>array('*'),
				),
			);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
			));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$w_id = '';
		if(isset(Yii::app()->session['w_id']) && !empty(Yii::app()->session['w_id'])){
			$w_id = Yii::app()->session['w_id'];
		}
		if(!$this->checkAccessByData('VendorProfileEditor', array('warehouse_id'=>$w_id))){
			Yii::app()->user->setFlash('premission_info', 'You dont have permission.!');
			Yii::app()->controller->redirect("index.php?r=vendor/admin&w_id=".$w_id);
		}
		
		$model=new Vendor;
		// Uncomment the following line if AJAX validation is needed
		$this->performAjaxValidation($model);

		if(isset($_POST['Vendor']))
		{
			$model->attributes=$_POST['Vendor'];
			//var_dump($model);die;
			// if($model->payment_days_range >= $model->credit_days){
			// 	Yii::app()->user->setFlash('error', 'Credit Days must be greater than Payment Days Range!');
			// 	$this->redirect(array('create'));

			// }
			$model->due_date = date('Y-m-d', strtotime($model->payment_start_date.' + '.$model->credit_days.' days'));
			$model->created_date = date('Y-m-d H:i:s');
			$model->allocated_warehouse_id = $w_id;
			if($model->save())
				$this->redirect(array('view','id'=>$model->id));
		}

		$this->render('create',array(
			'model'=>$model,
			));
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$w_id = '';
		if(isset(Yii::app()->session['w_id']) && !empty(Yii::app()->session['w_id'])){
			$w_id = Yii::app()->session['w_id'];
		}
		if(!$this->checkAccessByData('VendorProfileEditor', array('warehouse_id'=>$w_id))){
			Yii::app()->user->setFlash('premission_info', 'You dont have permission.!');
			Yii::app()->controller->redirect("index.php?r=vendor/admin&w_id=".$w_id);
		}

		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		$this->performAjaxValidation($model);

		if(isset($_POST['Vendor']))
		{
			$model->attributes=$_POST['Vendor'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->id));
		}

		$this->render('update',array(
			'model'=>$model,
			));
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
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$dataProvider=new CActiveDataProvider('Vendor');
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
			));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{	
		$w_id ='';
		if(isset($_GET['w_id'])){
			$w_id = $_GET['w_id'];
		}
		Yii::app()->session['w_id'] = $w_id;
		$model=new Vendor('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Vendor']))
			$model->attributes=$_GET['Vendor'];

		$this->render('admin',array(
			'model'=>$model,
			));
	}

	public function actionProductMap($vendor_id){
		$w_id = '';
		if(isset(Yii::app()->session['w_id']) && !empty(Yii::app()->session['w_id'])){
			$w_id = Yii::app()->session['w_id'];
		}
		if(!$this->checkAccessByData('VendorProductViewer', array('warehouse_id'=>$w_id))){
			Yii::app()->user->setFlash('premission_info', 'You dont have permission.!');
			Yii::app()->controller->redirect("index.php?r=vendor/admin&w_id=".$w_id);
		}
		//var_dump($_POST);die;
		//flash , transaction remaning, price = ''
		$criteria  = new CDbCriteria;
		$criteria->select  = 'name, bussiness_name, mobile';
		$criteria->condition = 'id = '.$vendor_id.' and status = 1';
		$model = Vendor::model()->find($criteria);
		if(!isset($model) && empty($model)){
			Yii::app()->user->setFlash('error', 'Either Vendor Id Wrong or Vendor Inactive');
			Yii::app()->controller->redirect('index.php?r=vendor/admin');
		}
		
		$data = new BaseProduct('search');
		$data->unsetAttributes();


		if(isset($_GET['BaseProduct'])){
			$data->attributes = $_GET['BaseProduct'];
			//print_r($_GET);die;
		}
		
		$update = array();
		if(isset($_POST['save'])){
			if(!$this->checkAccessByData('VendorProductEditor', array('warehouse_id'=>$w_id))){
				Yii::app()->user->setFlash('premission_info', 'You dont have permission.!');
				Yii::app()->controller->redirect("index.php?r=vendor/admin&w_id=".$w_id);
			}
			$baseProductIds = array();
			if(isset($_POST['baseProductIds']) && !empty($_POST['baseProductIds'])){
				$baseProductIds = $_POST['baseProductIds'];
			}
			$postPrice = array();
			if(isset($_POST['price']) && !empty($_POST['price'])){
				$postPrice = $_POST['price'];
			}
			$products = VendorDao::getVendorProductByIds(implode(',', $baseProductIds), $vendor_id);
			$newProducts = array();
			foreach ($_POST as $key => $value) {
				if(substr($key, 0, 9) == 'checkedId'){
					array_push($newProducts, substr($key, 10));
				}
			}
			foreach ($newProducts as $key => $value) {
				if(array_key_exists($value, $products)){
					$index = array_search($value, $baseProductIds);
					$price = ($postPrice[$index] != '') ? $postPrice[$index] : 0;
					if($price != $products[$value]['price']){
						$temp = array('id'=> $products[$value]['id'], 'price'=> $price);
						array_push($update, $temp);
					}
					unset($products[$value]);
					unset($newProducts[$key]);
				}
			}
			$ids = '';
			$first = true;
			foreach ($products as $key => $value) {
				if($first)
					$ids .= $value['id'];
				else{
					$ids .= ','.$value['id'];
				}
				$first = false;
			}
			$insert = array();
			foreach ($newProducts as $key => $value) {
				$index = array_search($value, $baseProductIds);
				$price = ($postPrice[$index] != '') ? $postPrice[$index] : 0;
				$temp = array('base_product_id' => $value, 'price' => $price);
				array_push($insert, $temp); 
			}
			VendorDao::updateVendorProductById($update);
			VendorDao::deleteVendorProductById($ids);
			VendorDao::insertVendorProductById($insert, $vendor_id);
		}

		$products = VendorDao::getVendorProductDetails($vendor_id);
		$this->render('productMap', array(
			'model' => $model,
			'dataProvider' => $data,
			'products' => $products,
			));
	}

	public function actionCreditManagement(){
		//var_dump($_POST);die;

		$w_id = '';
		if(isset(Yii::app()->session['w_id']) && !empty(Yii::app()->session['w_id'])){
			$w_id = Yii::app()->session['w_id'];
		}
		if(!$this->checkAccessByData('VendorCreditViewer', array('warehouse_id'=>$w_id))){
			Yii::app()->user->setFlash('premission_info', 'You dont have permission.!');
			Yii::app()->controller->redirect("index.php?r=vendor/admin&w_id=".$w_id);
		}


		if(!empty($_GET['date'])){
			$endDate = $_GET['date'];
		}
		else if(isset($_POST['VendorPayment']) && !empty($_POST['VendorPayment'])){
			$endDate = $_POST['VendorPayment']['date'];
		}
		else{
			$endDate = date('Y-m-d');
		}
		$initial_pending_date = VendorDao::getInitialPendingDate();
		//var_dump($initial_pending_date);die;
		if(strtotime($endDate) < strtotime($initial_pending_date)){
			
			$startDate = VendorDao::getLastPendingDate($endDate, $initial_pending_date);
		}
		else $startDate = $initial_pending_date;
		if(isset($_POST['Payment']) && !empty($_POST['creditRepaid'])){
			if(!$this->checkAccessByData('VendorCreditEditor', array('warehouse_id'=>$w_id))){
				Yii::app()->user->setFlash('premission_info', 'You dont have permission.!');
				Yii::app()->controller->redirect("index.php?r=vendor/creditManagement");
			}
			self::saveVendorPaymet($_POST, $endDate);
		}
		$nextDate = date('Y-m-d', strtotime($startDate.' + 1 day'));
		$totalPendingMap = VendorDao::getAllVendorPayableAmount($nextDate, $endDate);
		$initialPendingMap = VendorDao::getAllVendorInitialPending($startDate);
		$lastPaymentDetails = VendorDao::getVendorLastPaymentDetails();
		//var_dump($initialPendingMap);die;
		$totalPending = 0;
		// foreach ($initialPendingMap as $key => $value) {
		// 	$totalPending += $value;
		// 	if(array_key_exists($key, $totalPendingMap)){
		// 		$totalPending += $totalPendingMap[$key];
		// 	}
		// }
		//$totalPendingMap['total'] = strval($totalPending);
		$skuMap = VendorDao::getAllVendorSkus();
		$model = new Vendor('search');
		$model->unsetAttributes();
		if(isset($_GET['Vendor'])){
			$model->attributes = $_GET['Vendor'];
		}

		$vendorPayment = new VendorPayment;
		$vendorPayment->date = $endDate;
		$payable = VendorDao::getPayable($endDate, $nextDate);
		// var_dump($payable);die;
		$totalPayable = 0;
		// foreach ($payable as $key => $value) {
		// 	$totalPayable += $value['amount'];
		// 	$totalPayable += $initialPendingMap[$key];
		// }
		// $payable['total'] = strval($totalPayable);
		$this->render('creditManagement', array(
			'model' => $model,
			'dataProvider' =>$model,
			'skuMap' => $skuMap,
			'vendorPayment' => $vendorPayment,
			'payable' => $payable,
			'totalPendingMap' => $totalPendingMap,
			'initialPendingMap' => $initialPendingMap,
			'lastPaymentDetails' => $lastPaymentDetails,
			));
		//var_dump($skuMap);die;
	}

	public function actionVendorLedger($vendor_id){
		$w_id = '';
		if(isset(Yii::app()->session['w_id']) && !empty(Yii::app()->session['w_id'])){
			$w_id = Yii::app()->session['w_id'];
		}
		if(!$this->checkAccessByData('VendorLedgerViewer', array('warehouse_id'=>$w_id))){
			Yii::app()->user->setFlash('premission_info', 'You dont have permission.!');
			Yii::app()->controller->redirect("index.php?r=vendor/admin&w_id=".$w_id);
		}

		$vendor = Vendor::model()->findByPk($vendor_id);
		$payments = VendorPayment::model()->findAllByAttributes(array('vendor_id' => $vendor_id, 'status' => 1), array('order'=>'date asc'));
		$orders = VendorDao::getVendorOrderQuantity($vendor_id);
		$dataProvider = Vendor::getLedgerDataProvider($payments,$orders);
		$this->render('vendorLedger', array(
			'dataProvider' => $dataProvider,
			'vendor' => $vendor,));
		
	}

	

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Vendor the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=Vendor::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Vendor $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='vendor-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}

	public function saveVendorPaymet($post, $date){
		$vendorIds = $post['vendorIds'];
		$creditRepaid = $post['creditRepaid'];
		foreach ($vendorIds as $key => $value) {
			if($creditRepaid[$key] != ''){
				VendorPayment::saveVendorCashPayment($value, $creditRepaid[$key], $date);
			}
		}
	}

	public function actionInvoice($vendorId, $purchaseId){
		$model = VendorDao::getLineByPurchasId($vendorId, $purchaseId);
		//var_dump($model);die;
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
		$modelOrder= PurchaseHeader::model()->findByPk($purchaseId);
		$store = Store::model()->findByAttributes(array('store_id' => 1));
		$modelOrder->groots_address = $store->business_address;
		$modelOrder->groots_city = $store->business_address_city;
		$modelOrder->groots_state = $store->business_address_state;
		$modelOrder->groots_country = $store->business_address_country;
		$modelOrder->groots_pincode = $store->business_address_pincode;
		$modelOrder->groots_authorized_name = $store->store_name;
		$vendor = Vendor::model()->findByPk($vendorId);
		$this->createPdf($model,$newModel,$vendor, $modelOrder);
	}

	public function createPdf($model,$newModel,$vendor, $modelOrder){
		ob_start();
		echo $this->renderPartial('invoice' ,array('model' => $model,'newModel' => $newModel,
            'vendor'=>$vendor, 'modelOrder' => $modelOrder, 'type'=>'invoice'), true);//die;
		$content = ob_get_clean();
		require_once( dirname(__FILE__) . '/../extensions/html2pdf/html2pdf.php');
		$title = "Vendor Invoice";
		$downloadFileName=$vendor->name." (".substr($modelOrder->delivery_date, 0, 10).")"." ". $modelOrder->id.".pdf";

		try
		{
			$html2pdf = new HTML2PDF('P', 'A4', 'en');
			$html2pdf->pdf->SetTitle($title);
			$html2pdf->writeHTML($content, isset($_GET['vuehtml']));
			$html2pdf->Output($downloadFileName);
			var_dump($html2pdf);


		}
		catch(HTML2PDF_exception $e) {
			echo $e;
			exit;
		}
	}
}