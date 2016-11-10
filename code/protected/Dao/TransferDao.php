<?php
/**
 * Created by PhpStorm.
 * User: manmohan
 * Date: 10/11/16
 * Time: 4:04 PM
 */

Yii::import('application.controllers.PurchaseHeaderController', true) ;

class TransferDao
{
    public static function createTransfer($w_id, $date){
        if($w_id==SOURCE_WH_ID) {
            $warehouses = Warehouse::model()->findAllByAttributes(array('status'=>1), array('select'=>'id'));
            foreach ($warehouses as $warehouse){
                if($warehouse->id != $w_id && $w_id != HD_OFFICE_WH_ID){
                    self::updateDailyTransferOrder($warehouse->id, $date);
                }
            }
            self::updateDailyTransferOrder($w_id, $date);
        }
        else{
            self::updateDailyTransferOrder($w_id, $date);
        }
    }

    public static function updateDailyTransferOrder($w_id, $date){
        //$orderLines = OrderLine::getOrderSumByDate($w_id, $date);
        $invHeadMap = InventoryHeaderDao::getInventoryHeaderMapByBpId($w_id);
        $quantitiesMap = TransferHeader::getTransferInCalculationData($w_id, $date);
        $transferOrderMap = array();

        foreach ($invHeadMap as $bp_id => $inv) {

            $s_inv = 0;
            if (isset($quantitiesMap['avgOrder'][$bp_id]) && isset($invHeadMap[$bp_id])) {
                $avgOrderInKg = $quantitiesMap['avgOrder'][$bp_id];
                $sch_inv_type = $invHeadMap[$bp_id]->schedule_inv_type;
                $sch_inv_no = $invHeadMap[$bp_id]->schedule_inv;
                if ($sch_inv_type == 'days') {
                    $s_inv = $sch_inv_no * $avgOrderInKg;
                } elseif ($sch_inv_type == 'percents') {
                    $s_inv = $sch_inv_no * $avgOrderInKg / 100;
                }

            }
            $prev_day_inv = empty($quantitiesMap['prevDayInv'][$bp_id]) ? 0 : $quantitiesMap['prevDayInv'][$bp_id];

            //$cur_inv =  empty($data->present_inv) ? 0 : $data->present_inv ;
            //$liq_inv =  empty($data->liquid_inv) ? 0 : $data->liquid_inv ;
            $order_sum = empty($quantitiesMap['orderSum'][$bp_id]) ? 0 : $quantitiesMap['orderSum'][$bp_id];
            $purchase = empty($quantitiesMap['purchaseSum'][$bp_id]) ? 0 : $quantitiesMap['purchaseSum'][$bp_id];
            $transIn_other = empty($quantitiesMap['transferInSum'][$bp_id]) ? 0 : $quantitiesMap['transferInSum'][$bp_id];
            $trans_out = empty($quantitiesMap['transferOutSum'][$bp_id]) ? 0 : $quantitiesMap['transferOutSum'][$bp_id];

            $extra_inv_absolute = $invHeadMap[$bp_id]->extra_inv * ($order_sum - $prev_day_inv + $trans_out + $s_inv) / 100;
            if ($extra_inv_absolute < 0) {
                $extra_inv_absolute = 0;
            }

            $trans_in = $s_inv + $order_sum + $trans_out + $extra_inv_absolute - ($purchase + $prev_day_inv + $transIn_other);

            if (empty($trans_in) || $trans_in < 0) {
                $trans_in = 0;
            }
            $transferOrderMap[$bp_id] = $trans_in;
            //echo "transin-".$trans_in."\n";

        }
        if($w_id==SOURCE_WH_ID){
            PurchaseHeaderController::createProcurementOrder($transferOrderMap, $date, $w_id);
        }
        else{
            self::createTransferOrder($transferOrderMap, $date, $w_id);
        }


    }

    private static function createTransferOrder($transferOrderMap, $date, $w_id){
        $warehouse = Warehouse::model()->findByAttributes(array('id' => $w_id), array('select' => 'default_source_warehouse_id'));
        $transferOrder = TransferHeader::model()->findByAttributes(array('delivery_date' => $date, 'source_warehouse_id' => $warehouse->default_source_warehouse_id, 'dest_warehouse_id' => $w_id, 'transfer_type' => 'regular'));

        $transaction = Yii::app()->db->beginTransaction();
        try {

            if (empty($transferOrder)) {
                $transferOrder = new TransferHeader();
                $transferOrder->source_warehouse_id = $warehouse->default_source_warehouse_id;
                $transferOrder->dest_warehouse_id = $w_id;
                $transferOrder->delivery_date = $date;
                $transferOrder->status = 'pending';
                $transferOrder->comment = 'system generated';
                $transferOrder->created_at = date('Y-m-d');
                $transferOrder->transfer_type = "regular";
            }
            $transferOrder->save();
            $transferLineMap = self::getTransferLineMap($transferOrder->id);
            foreach ($transferOrderMap as $bp_id => $qty) {
                if (isset($transferLineMap[$bp_id])) {
                    $item = $transferLineMap[$bp_id];
                } else {
                    $item = new TransferLine();
                    $item->transfer_id = $transferOrder->id;
                    $item->base_product_id = $bp_id;
                    $item->status = 'pending';
                    $item->created_at = date('Y-m-d');
                }
                $item->order_qty = $qty;
                //var_dump($item);
                $item->save();
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::app()->user->setFlash('error', 'Transfer order Creation failed.');
            throw $e;
            Yii::app()->controller->redirect(Yii::app()->request->urlReferrer);
        }

    }

    private static function getTransferLineMap($transfer_id){
        $itemArr = array();
        $items = TransferLine::model()->findAllByAttributes(array('transfer_id'=>$transfer_id));
        foreach ($items as $item){
            $itemArr[$item->base_product_id] = $item;
        }
        return $itemArr;
    }


}