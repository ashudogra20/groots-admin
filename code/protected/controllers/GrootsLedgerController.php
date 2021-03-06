<?php

class GrootsLedgerController extends Controller
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
				'actions'=>array('index','view','report'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update','report'),
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
		$model=new GrootsLedger;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['GrootsLedger']))
		{
			$model->attributes=$_POST['GrootsLedger'];
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
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['GrootsLedger']))
		{
			$model->attributes=$_POST['GrootsLedger'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->id));
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}

	public function actionreport()
	{    
        $model=new GrootsLedger;
         
         if (isset($_POST['filter'])) {
             $start_date = $_POST['GrootsLedger']['created_at'];
             $end_date = $_POST['GrootsLedger']['inv_created_at'];

             $cDate = date("Y-m-d", strtotime($start_date));
             $cdate1 = date("Y-m-d", strtotime($end_date));
             if ($cDate > $cdate1)
              {
                 Yii::app()->user->setFlash('error', 'End date always greater than Start date');
                  Yii::app()->controller->redirect("index.php?r=GrootsLedger/report");
             }

            else
            {
                ob_clean();
                 $data= $model->downloadCSVByIDs($cDate,$cdate1);
                 ob_flush();
                 exit();
             }
          
        }

        if (isset($_POST['client'])) {

           	$start_date = $_POST['tocdate'];
             
            /*if($_POST['CatLevel1']==0)   
            {
            	Yii::app()->user->setFlash('error', 'Client not selected');
                Yii::app()->controller->redirect("index.php?r=GrootsLedger/report");
            } */
            if ($start_date !='')
            {
           
            $cDate = date("Y-m-d", strtotime($start_date));
           
                        	ob_clean();
                $data= $model->downloadCSVByCIDs($cDate);
                ob_flush();
                exit();
            }
            else
            {
            	 Yii::app()->user->setFlash('error', 'Date not selected');
                 Yii::app()->controller->redirect("index.php?r=GrootsLedger/report");
            }
          
          
        }




      $this->render('report',array(
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
		$dataProvider=new CActiveDataProvider('GrootsLedger');
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model=new GrootsLedger('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['GrootsLedger']))
			$model->attributes=$_GET['GrootsLedger'];

		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return GrootsLedger the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=GrootsLedger::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param GrootsLedger $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='groots-ledger-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
