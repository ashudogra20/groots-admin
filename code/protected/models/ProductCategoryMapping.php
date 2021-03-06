<?php

/**
 * This is the model class for table "product_category_mapping".
 *
 * The followings are the available columns in table 'product_category_mapping':
 * @property string $base_product_id
 * @property string $category_id
 */
class ProductCategoryMapping extends CActiveRecord
{
    public $base_product_id;
    public $category_id;
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'product_category_mapping';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('base_product_id, category_id', 'required'),
			array('base_product_id, category_id', 'length', 'max'=>10),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('base_product_id, category_id', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'base_product_id' => 'Base Product',
			'category_id' => 'Category',
		);
	}

	/**
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
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('base_product_id',$this->base_product_id,true);
		$criteria->compare('category_id',$this->category_id,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

         public function getRecordById($base_product_id) {
            $connection = Yii::app()->db;
            $command = $connection->createCommand("select * from product_category_mapping where base_product_id=". $base_product_id);
            //echo '<pre>';print_r($command);die;
            $row = $command->queryAll();
            return $row;
        }
         public function getImageById($base_product_id) {
            $connection = Yii::app()->db;
            $command = $connection->createCommand("select * from media where base_product_id=". $base_product_id);
            //echo '<pre>';print_r($command);die;
            $row = $command->queryAll();
            return $row;
        }
    
    
	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return ProductCategoryMapping the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
