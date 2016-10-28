<?php
/**
 * Created by PhpStorm.
 * User: manmohan
 * Date: 15/9/16
 * Time: 3:54 PM
 */

class InventoryHeader extends CActiveRecord
{

    /*public $start_date="";
    public $end_date="";*/
    public $item_title="";
    public $date="";
    public $prev_day_inv=0;
    public $present_inv=0;
    public $liquid_inv=0;
    public $extra_inv_absolute=0;
    public $wastage = 0;
    public $liquidation_wastage = 0;
    public $balance = 0;
    public $schedule_inv_absolute=0;
    public $parent_id='';
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'groots_orders.inventory_header';
    }


    public function getDbConnection() {
        return Yii::app()->secondaryDb;
    }

    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('id,warehouse_id,base_product_id,schedule_inv,schedule_inv_type,extra_inv,extra_inv_type,created_at,item_title,date,parent_id', 'safe', 'on' => 'search,update'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
// NOTE: you may need to adjust the relation name and the related
// class name for the relations automatically generated below.
        return array(
            'Warehouse' => array(self::BELONGS_TO, 'Warehouse', 'warehouse_id'),
            'BaseProduct' => array(self::BELONGS_TO,  'BaseProduct', 'base_product_id'),
            //'Inventory' => array(self::HAS_ONE,  'Inventory', 'inv_id'),
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
    /*public function search() {
        // @todo Please modify the following code to remove attributes that should not be searched.

        $criteria = new CDbCriteria;

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }*/



    public function search() {
        // @todo Please modify the following code to remove attributes that should not be searched.
        if(empty($this->date)){
            $this->date = date('Y-m-d');
        }
        $criteria = new CDbCriteria;
        $criteria->select = 't.*, bp.title as item_title, bp.parent_id as parent_id, inv.present_inv, inv.wastage, inv.liquidation_wastage, inv.extra_inv as extra_inv_absolute, inv.liquid_inv';
        /*$criteria->with = array(
            'BaseProduct' => array('alias'=> 't1', 'together' => true, ),
        );*/
        $criteria->join = " Left join groots_orders.inventory inv on (inv.base_product_id = t.base_product_id  and inv.date ='".$this->date."') ";
        $criteria->join .= " join cb_dev_groots.base_product bp on bp.base_product_id = t.base_product_id  ";
        //$criteria->together = true;
        $criteria->compare( 'bp.title', $this->item_title, true );

        //$criteria->compare('i.date', $this->date, true);
        $criteria->order = 'bp.title asc';

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'sort'=>array(
                'attributes'=>array(
                    'item_title'=>array(
                        'asc'=>'bp.title',
                        'desc'=>'bp.title DESC',
                    ),
                    '*',
                ),
            ),
            'pagination' => array(
                'pageSize' => 150,
            ),
        ));
    }




    /*public function searchNew() {
        // @todo Please modify the following code to remove attributes that should not be searched.
        $issuperadmin = Yii::app()->session['is_super_admin'];
        if ($issuperadmin) {
            $store_id = Yii::app()->session['brand_admin_id'];
        } else {
            $store_id = Yii::app()->session['brand_id'];
        }
        $criteria = new CDbCriteria;
        //$criteria->condition = 'order_id > 35';
        $criteria->select = " t.*, bp.title";
        $criteria->join .= ' join cb_dev_groots.base_product bp on bp.base_product_id = t.base_product_id ';
        //$criteria->order = 'created_date DESC';
        $criteria->compare('bp.title', $this->item_title, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'sort'=>array(
                'defaultOrder'=>'created_date DESC',
            ),
            'pagination' => array(
                'pageSize' => 100,
            ),

        ));
    }*/


    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return Retailer the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }


    /*public function getStartDate(){
        return $this->start_date;
    }

    public function getEndDate(){
        return $this->end_date;
    }*/

    public function getItemTitle(){
        return $this->item_title;
    }

    public function getDate(){
        return $this->date;
    }

    public function getPrevDayInv(){
        return $this->prev_day_inv;
    }

    public function getPresentInv(){
        return $this->present_inv;
    }

    public function getLiquidInv(){
        return $this->liquid_inv;
    }

    public function getExtraInvAbsolute(){
        return $this->extra_inv_absolute;
    }

    public function getWastage(){
        return $this->wastage;
    }

    public function getLiquidationWastage(){
        return $this->liquidation_wastage;
    }

    public function getBalance(){
        return $this->balance;
    }

    public function getScheduleInvAbsolute(){
        return $this->schedule_inv_absolute;
    }

    public function getParentId(){
        return $this->parent_id;
    }
    public static function extraInvType(){
        $connection = Yii::app()->secondaryDb;
        $extraInvTypes = Utility::get_enum_values($connection, self::tableName(), 'extra_inv_type' );
        return $extraInvTypes;
    }
    public static function scheduleInvType(){
        $connection = Yii::app()->secondaryDb;
        $scheduleInvTypes = Utility::get_enum_values($connection, self::tableName(), 'schedule_inv_type' );
        return $scheduleInvTypes;
    }


}