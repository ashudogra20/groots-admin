<?php
/**
 * Created by PhpStorm.
 * User: manmohan
 * Date: 13/9/16
 * Time: 7:36 PM
 */


class Inventory extends CActiveRecord
{

    public $order_qty = '';
    public $transferIn_qty = '';
    public $transferOut_qty = '';
    public $purchase_qty = '';
    public $balance = '';
    public $item_title = '';
    public $start_date="";
    public $end_date="";
    public $order_id='';
    public $prev_day_inv='';
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'inventory';
    }


    public function getDbConnection() {
        return Yii::app()->secondaryDb;
    }

    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('id,inv_id,warehouse_id,base_product_id,schedule_inv,present_inv,wastage,extra_inv,inv_change_type,inv_change_id,inv_change_quantity,liquid_inv,liquidation_wastage,date,created_at,item_title', 'safe', 'on' => 'search,update'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
// NOTE: you may need to adjust the relation name and the related
// class name for the relations automatically generated below.
        return array(
            'InvHeader' => array(self::BELONGS_TO, 'InventoryHeader', 'inv_id'),
            'Warehouse' => array(self::BELONGS_TO, 'Warehouse', 'warehouse_id'),
            'BaseProduct' => array(self::BELONGS_TO,  'BaseProduct', 'base_product_id'),
        );
    }


    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(

        );
    }

    /*
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search() {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria = new CDbCriteria;
        $criteria->with = array(
            'BaseProduct' => array('alias'=> 't1', 'together' => true, ),
            'InvHeader' => array('alias'=> 't2', 'together' => true, ),
        );
        $criteria->together = true;
        //$criteria->select = " t.*";
        $criteria->compare( 't1.title', $this->item_title, true );
        $criteria->compare( 't2.warehouse_id', $this->warehouse_id, true );
        $criteria->compare( 'date', $this->date, true );
        if($this->start_date){
            $criteria->addCondition("date >= '".$this->start_date."''");
        }
        if($this->end_date){
            $criteria->addCondition("date <= '".$this->end_date."''");
        }
        $criteria->order = 'date desc, title asc';

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'sort'=>array(
                'attributes'=>array(
                    'date'=>array(
                        'asc'=>'date',
                        'desc'=>'date DESC',
                    ),
                    'item_title'=>array(
                        'asc'=>'BaseProduct.title',
                        'desc'=>'BaseProduct.title DESC',
                    ),
                    '*',
                ),
            ),
            'pagination' => array(
                'pageSize' => 100,
            ),
        ));
    }

    public static function getTotalInvOfDate($w_id, $date){
        $prevDay = Utility::getPrevDay($date);
        $sql1 = "select sum(schedule_inv) as schedule_inv, sum(present_inv) as present_inv, sum(wastage) as wastage, sum(liquid_inv) as liquid_inv, sum(liquidation_wastage) as liquidation_wastage, sum(secondary_sale) as secondary_sale from inventory i join cb_dev_groots.base_product bp on bp.base_product_id = i.base_product_id  where date = '" . $date . "' and warehouse_id=".$w_id." and ( bp.parent_id is null or bp.parent_id=0) group by date";
        $sql2 = "select sum(present_inv) as present_inv, sum(liquid_inv) as liquid_inv from inventory i join cb_dev_groots.base_product bp on bp.base_product_id = i.base_product_id where date = '" . $prevDay . "' and warehouse_id=".$w_id." and ( parent_id is null or parent_id=0) group by date";
        $connection = Yii::app()->secondaryDb;
        $command1 = $connection->createCommand($sql1);
        $invArr = $command1->queryAll();
        if(empty($invArr)){
            $tmp = array();
            $tmp['schedule_inv'] = 0;
            $tmp['present_inv'] = 0;
            $tmp['wastage'] = 0;
            $tmp['liquid_inv'] = 0;
            $tmp['liquidation_wastage'] = 0;
            $tmp['secondary_sale'] = 0;
            $invArr = array();
            array_push($invArr, $tmp);
        }
        //var_dump($invArr);die;
        $command2 = $connection->createCommand($sql2);
        $prevDayInv = $command2->queryAll();
        if(empty($prevDayInv)){
            $invArr[0]['prev_day_inv'] = 0;
            $invArr[0]['prev_day_liq_inv'] = 0;
        }
        else{
            $invArr[0]['prev_day_inv'] = $prevDayInv[0]['present_inv'];
            $invArr[0]['prev_day_liq_inv'] = $prevDayInv[0]['liquid_inv'];
        }
        //var_dump($prevDayInv);die;
        $invArr[0]['id'] = 1;
        $invArr[0]['balance'] = 0;
        //$invArr[0]['total_order'] = OrderHeader::getTotalOrderOfDate($date);
        //var_dump($inventory);die;
        return $invArr;
    }

    public static function getInventoryCalculationData($w_id, $date){
        //echo "<pre>";
        $baseProducts = BaseProduct::model()->findAllByAttributes(array('status'=>1), array('select'=>'base_product_id', 'condition'=>'parent_id is null or parent_id = 0'));
        //print_r($baseProducts);die;
        $baseProductArr = array();
        foreach ($baseProducts as $baseProduct){
            array_push($baseProductArr, $baseProduct->base_product_id);
        }
        $quantitiesMap = array();
        $prevDayInv = self::getPrevDayInvMap($w_id, $date);
        $prevDayLiqInv = self::getPrevDayLiqInvMap($w_id, $date);
        $orderSum = OrderLine::getDeliveredOrderSumByDate($w_id, $date);//print_r($orderSum);die;
        $purchaseSum = PurchaseLine::getReceivedPurchaseSumByDate($w_id, $date);
        $transferInSum = TransferLine::getTransferInSumByDate($w_id,$date);
        $transferOutSum = TransferLine::getDeliveredTransferOutSumByDate($w_id,$date);
        //$toBeSentLiqInv = TransferLine::getLastDayLiqInv($w_id, $date);
        $sentLiqInv = TransferLine::getLiqInvSent($w_id, $date);
        $receivedLiqInv = TransferLine::getLiqInvReceived($w_id, $date);
        $avgOrderByItem = OrderHeader::getAvgOrderByItem($w_id, $date);
//print_r($transferOutSum);die;
        $totalPurchase = 0;
        $totalOrder = 0;
        $totalTransferIn = 0;
        $totalTransferOut = 0;
        //$totalToBeSentLiqInv = 0;
        $totalSentLiqInv = 0;
        $totalReceivedLiqInv = 0;

        foreach ($purchaseSum as $bp_id => $purchase){
            if(in_array($bp_id, $baseProductArr))
                $totalPurchase += $purchase;
        }

        foreach ($orderSum as $bp_id => $order){
            //if(in_array($bp_id, $baseProductArr))
                $totalOrder += $order;
        }

        foreach ($transferInSum as $bp_id => $transferIn){
            if(in_array($bp_id, $baseProductArr))
                $totalTransferIn += $transferIn;
        }

        foreach ($transferOutSum as $bp_id => $transferOut){
            if(in_array($bp_id, $baseProductArr))
                $totalTransferOut += $transferOut;
        }

        /*foreach ($toBeSentLiqInv as $liqInv){
            $totalToBeSentLiqInv += $liqInv;
        }*/

        foreach ($sentLiqInv as $bp_id => $liqInv){
            if(in_array($bp_id, $baseProductArr))
                $totalSentLiqInv += $liqInv;
        }

        foreach ($receivedLiqInv as $bp_id => $liqInv){
            if(in_array($bp_id, $baseProductArr))
                $totalReceivedLiqInv += $liqInv;
        }

        $quantitiesMap['prevDayInv'] = $prevDayInv;
        $quantitiesMap['orderSum'] = $orderSum;
        $quantitiesMap['purchaseSum'] = $purchaseSum;
        $quantitiesMap['transferInSum'] = $transferInSum;
        $quantitiesMap['transferOutSum'] = $transferOutSum;
        $quantitiesMap['avgOrder'] = $avgOrderByItem;
        $quantitiesMap['prevDayLiqInv'] = $prevDayLiqInv;
        //$quantitiesMap['toBeSentLiqInv'] = $toBeSentLiqInv;
        $quantitiesMap['sentLiqInv'] = $sentLiqInv;
        $quantitiesMap['receivedLiqInv'] = $receivedLiqInv;

        $quantitiesMap['totalPurchase'] = $totalPurchase;
        $quantitiesMap['totalOrder'] = $totalOrder;
        $quantitiesMap['totalTransferIn'] = $totalTransferIn;
        $quantitiesMap['totalTransferOut'] = $totalTransferOut;
        //$quantitiesMap['totalToBeSentLiqInv'] = $totalToBeSentLiqInv;
        $quantitiesMap['totalSentLiqInv'] = $totalSentLiqInv;
        $quantitiesMap['totalReceivedLiqInv'] = $totalReceivedLiqInv;

        return $quantitiesMap;
    }

    public static function getPrevDayInvMap($w_id, $today){
        $invArr = array();
        $prevDay = Utility::getPrevDay($today);
        $invs = Inventory::model()->findAllByAttributes(array('warehouse_id'=>$w_id,'date'=>$prevDay), array('select'=>'base_product_id, present_inv'));
        foreach ($invs as $inv){
            $invArr[$inv->base_product_id] = $inv->present_inv;
        }
        return $invArr;
    }

    public static function getPrevDayLiqInvMap($w_id,$today){
        $invArr = array();
        $prevDay = Utility::getPrevDay($today);
        $invs = Inventory::model()->findAllByAttributes(array('warehouse_id'=>$w_id,'date'=>$prevDay), array('select'=>'base_product_id, liquid_inv'));
        foreach ($invs as $inv){
            $invArr[$inv->base_product_id] = $inv->liquid_inv;
        }
        return $invArr;
    }
    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Retailer the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function getOrderQty(){
        return $this->order_qty;
    }

    public function getTransferInQty(){
        return $this->transferIn_qty;
    }

    public function getTransferOutQty(){
        return $this->transferOut_qty;
    }

    public function getPurchaseQty(){
        return $this->purchase_qty;
    }

    public function getBalance(){
        return $this->balance;
    }

    public function getItemTitle(){
        return $this->item_title;
    }

    public function getStartDate(){
        return $this->start_date;
    }

    public function getEndDate(){
        return $this->end_date;
    }

    public function getOrderId(){
        return $this->order_id;
    }

    public function getPrevDayInv(){
        return $this->prev_day_inv;
    }

}