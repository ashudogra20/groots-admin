<?php

class PurchaseHeaderController extends Controller
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
				'actions'=>array('create','update', 'admin','downloadProcurementReport'),
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

    protected function beforeAction() {
        $w_id='';
        if(parent::beforeAction()){
            if(isset($_GET['w_id'])){
                $w_id = $_GET['w_id'];
            }
            if($w_id>0 && $this->checkAccessByData('PurchaseViewer', array('warehouse_id'=>$w_id))){
                return true;
            }
            if($w_id>0 && $this->checkAccessByData('ProcurementViewer', array('warehouse_id'=>$w_id))){
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
	    //echo "<pre>";
        $w_id = '';
        if(isset($_GET['w_id'])){
            $w_id = $_GET['w_id'];
        }

        if(!$this->checkAccessByData('ProcurementEditor', array('warehouse_id'=>$w_id))){
            Yii::app()->user->setFlash('premission_info', 'You dont have permission.');
            Yii::app()->controller->redirect("index.php?r=purchaseHeader/admin&w_id=".$w_id);
        }
		$model=new PurchaseHeader('search');

        list($popularItems, $otherItems) = BaseProduct::PopularItems();
        //print_r($popularItems);die;
        $dataProvider=new CArrayDataProvider($popularItems, array(
            'pagination'=>array(
                'pageSize'=>300,
            ),
        ));
//print_r($_POST);die;
        //print_r($otherItems);die;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['purchase-create']))
		{
            $transaction = Yii::app()->db->beginTransaction();
            try {

                $model->attributes=$_POST['PurchaseHeader'];
                $model->created_at = date('Y-m-d');
                //print_r($model);die;

                if($model->total_payable_amount > 0 && $model->save()){

                    foreach ($_POST['price'] as $key => $price) {
                        if ($price > 0) {
                            $purchaseLine = new PurchaseLine();
                            $purchaseLine->purchase_id = $model->id;
                            $purchaseLine->base_product_id = $_POST['base_product_id'][$key];
                            if(isset($_POST['order_qty'][$key]) && $_POST['order_qty'][$key] > 0){
                                $purchaseLine->order_qty = $_POST['order_qty'][$key];
                            }
                            if(isset($_POST['received_qty'][$key]) && $_POST['received_qty'][$key] > 0){
                                $purchaseLine->received_qty = $_POST['received_qty'][$key];
                            }

                            //$purchaseLine->unit_price = $_POST['store_offer_price'][$key];
                            $purchaseLine->price = $price;
                            $purchaseLine->created_at = date("y-m-d H:i:s");
                            $purchaseLine->save();
                        }
                    }
                    $transaction->commit();
                    $this->redirect(array('admin','w_id'=>$model->warehouse_id));
                }
                else{
                    Yii::app()->user->setFlash('error', 'Purchase order Creation failed.');
                }
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::app()->user->setFlash('error', 'Purchase order Creation failed.');
                throw $e;
            }

		}

		$this->render('create',array(
			'model'=>$model,
            'dataProvider'=>$dataProvider,
            'otherItems'=> $otherItems,
            'w_id' => $w_id,
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
		//print_r($_POST);die;
        $w_id = '';
        if(isset($_GET['w_id'])){
            $w_id = $_GET['w_id'];
        }
        /*if(!($this->checkAccess('ProcurementEditor', array('warehouse_id'=>$w_id)) || $this->checkAccess('PurchaseEditor',array('warehouse_id'=>$w_id)))){
            Yii::app()->controller->redirect("index.php?r=purchaseHeader/admin&w_id=".$w_id);
        }*/
        if(!($this->checkAccessByData(array('ProcurementEditor', 'PurchaseEditor'), array('warehouse_id'=>$w_id)))){
            Yii::app()->controller->redirect("index.php?r=purchaseHeader/admin&w_id=".$w_id);
        }

        $model=$this->loadModel($id);
        $purchaseLines = PurchaseLine::model()->findAllByAttributes(array('purchase_id' => $id));
        list($popularItems, $otherItems) = BaseProduct::PopularItems();
        $purchaseLineMap = array();
        $purchaseLinesArr = array();
        foreach ($purchaseLines as $item){
            //var_dump($item->BaseProduct); die;
            $purchaseLineMap[$item->base_product_id] = $item;
            array_push($purchaseLinesArr,$item);
        }

        $dataProvider=new CArrayDataProvider($purchaseLinesArr, array(
            'pagination'=>array(
                'pageSize'=>100,
            ),
        ));
		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

        if(isset($_POST['purchase-update'])) {
            $transaction = Yii::app()->db->beginTransaction();
            try {

                $model->attributes = $_POST['PurchaseHeader'];

                if ($model->save()) {
                    foreach ($_POST['price'] as $key => $price) {
                        //$orderQt = $_POST['product_qty'][$key];
                        if ($price > 0 ) {
                            if(isset($purchaseLineMap[$_POST['base_product_id'][$key]])){
                                $purchaseLine = $purchaseLineMap[$_POST['base_product_id'][$key]];
                            }
                            else{
                                $purchaseLine = new PurchaseLine();
                                $purchaseLine->purchase_id = $model->id;
                                $purchaseLine->base_product_id = $_POST['base_product_id'][$key];
                                $purchaseLine->created_at = date("y-m-d H:i:s");

                            }

                            if(isset($_POST['order_qty'][$key]) && $_POST['order_qty'][$key] > 0){
                                $purchaseLine->order_qty = $_POST['order_qty'][$key];
                            }

                            if(isset($_POST['received_qty'][$key]) && $_POST['received_qty'][$key] > 0){
                                $purchaseLine->received_qty = $_POST['received_qty'][$key];
                            }

                            $purchaseLine->price = $price;

                            $purchaseLine->save();
                        }
                        else{
                            if(isset($purchaseLineMap[$_POST['base_product_id'][$key]])){
                                $purchaseLine = $purchaseLineMap[$_POST['base_product_id'][$key]];
                                $purchaseLine->deleteByPk($purchaseLine->id);
                            }

                        }
                    }
                    $transaction->commit();
                    $this->redirect(array('admin','w_id'=>$model->warehouse_id));
                }
                else{
                    Yii::app()->user->setFlash('error', 'Purchase order Update failed.');
                }
            }catch (\Exception $e) {
                $transaction->rollBack();
                Yii::app()->user->setFlash('error', 'Purchase order Creation failed.');
                throw $e;
            }
        }


		$this->render('update',array(
			'model'=>$model,
            'purchaseLines'=> $purchaseLines,
            'dataProvider'=>$dataProvider,
            'otherItems'=> $otherItems,
            'w_id' => $_GET['w_id'],
            'update'=>true,
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
		$dataProvider=new CActiveDataProvider('PurchaseHeader');
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new PurchaseHeader('search');
		$model->unsetAttributes();  // clear any default values
        $w_id = $_GET['w_id'];
		if(isset($_GET['PurchaseHeader']))
			$model->attributes=$_GET['PurchaseHeader'];
        $model->warehouse_id=$w_id;
        $showCreate = false;
        if($w_id>0 && $this->checkAccessByData('ProcurementViewer', array('warehouse_id'=>$w_id))){
            $showCreate = true;
        }
		$this->render('admin',array(
			'model'=>$model,
            'w_id' => $w_id,
            'showCreate'=>$showCreate,
        ));
	}


    public function actionDownloadProcurementReport(){
        $w_id = $_GET['w_id'];
        $date = $_GET['date'];
        $sql = 'select bp.title, pl.base_product_id, sum(pl.order_qty) from groots_orders.purchase_line as pl
                left join groots_orders.purchase_header as ph
                on ph.id = pl.purchase_id
                left join cb_dev_groots.base_product as bp 
                on bp.base_product_id = pl.base_product_id
                where ph.delivery_date = '."'".$date."'".'and warehouse_id = '.$w_id.' and ph.status = '."'pending'".'
                group by pl.base_product_id';
        echo $sql;
        $connection = Yii::app()->secondaryDb;
        $command = $connection->createCommand($sql);
        $command->execute();
        $dataArray = $command->queryAll();
        $fileName = $date."procurement_report";
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

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return PurchaseHeader the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=PurchaseHeader::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param PurchaseHeader $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='purchase-header-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
