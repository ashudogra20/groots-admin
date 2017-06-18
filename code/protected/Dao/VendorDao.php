<?php 

class VendorDao
{
    public function getProcExecutiveDropdownData()
    {
        $connection = Yii::app()->db;
        $sql = 'select ge.id, ge.name from cb_dev_groots.groots_employee as ge left join employee_department ed on ed.employee_id = ge.id where ed.department_id = 2 and ge.status = 1';
        //echo $sql; die;
        $command = $connection->createCommand($sql);
        $command->execute();
        $data = $command->queryAll();
        $array = array();
        foreach ($data as $key => $value) {
            $array[$value['id']] = $value['name'];
        }
        return $array;
    }

    public function getVendorProductList($productId, $w_id)
    {
        $connection = Yii::app()->secondaryDb;
        $sql = 'select vpm.vendor_id, v.bussiness_name from cb_dev_groots.vendor_product_mapping as vpm left join cb_dev_groots.vendors as v on v.id = vpm.vendor_id where vpm.base_product_id = ' . $productId . ' and v.allocated_warehouse_id = ' . $w_id;
        //echo $sql; die;
        $command = $connection->createCommand($sql);
        $command->execute();
        $data = $command->queryAll();
        //var_dump($data);die;
        $array = array();
        foreach ($data as $key => $value) {
            $array[$value['vendor_id']] = $value['bussiness_name'];
        }
        //var_dump($array);die;
        return $array;
    }

    public function getVendorProductDetails($vendor_id)
    {
        $sql = 'select id, base_product_id, price from cb_dev_groots.vendor_product_mapping where vendor_id = ' . $vendor_id;
        $connection = Yii::app()->secondaryDb;
        $command = $connection->createCommand($sql);
        $command->execute();
        $data = $command->queryAll();
        $result = array();
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $result[$value['base_product_id']] = $value;
            }
        }
        return $result;
    }

    public function updateVendorProductById($array)
    {
        foreach ($array as $key => $value) {
            $sql = 'update vendor_product_mapping set price = ' . $value['price'] . ' where id = ' . $value['id'];
            $connection = Yii::app()->db;
            $command = $connection->createCommand($sql);
            $command->execute();
        }
    }

    public function deleteVendorProductById($ids)
    {
        if (empty($ids))
            return;
        else {
            $sql = 'delete from vendor_product_mapping where id in (' . $ids . ')';
            $connection = Yii::app()->db;
            $command = $connection->createCommand($sql);
            $command->execute();
        }
    }

    public function insertVendorProductById($array, $vendor_id)
    {
        if (empty($array) || count($array) == 0) {
            return;
        } else {
            $userId = Yii::app()->user->getId();
            $sql = 'insert into vendor_product_mapping values';
            $values = '';
            $count = 1;
            foreach ($array as $key => $value) {
                $values .= '(null,' . $vendor_id . ', ' . $value['base_product_id'] . ', ' . $value['price'] . ', 1, CURDATE(), NOW(), ' . $userId . ')';
                if ($count != count($array)) {
                    $values .= ', ';
                }
                $count++;
            }
            $sql .= $values;
            $connection = Yii::app()->db;
            //echo $sql;die;
            $command = $connection->createCommand($sql);
            $command->execute();
        }
    }

    public function getVendorTypeDropdownData()
    {
        $connection = Yii::app()->db;
        $result = Utility::get_enum_values($connection, 'vendors', 'vendor_type');
        $vendorTypes = array();
        foreach ($result as $key => $value) {
            $vendorTypes[$value['value']] = $value['value'];
        }
        return $vendorTypes;
    }

    public function getAllVendorSkus()
    {
        $connection = Yii::app()->db;
        $sql = 'select bp.title , vpm.vendor_id from cb_dev_groots.vendor_product_mapping as vpm left join base_product as bp on vpm.base_product_id = bp.base_product_id where bp.priority != 1';
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $map = array();
        foreach ($result as $key => $value) {
            if (!isset($map[$value['vendor_id']])) {
                $map[$value['vendor_id']] = array();
                array_push($map[$value['vendor_id']], $value['title']);
            } else {
                array_push($map[$value['vendor_id']], $value['title']);
            }
        }
        return $map;
    }

    public function getAllVendorPayableAmount($startDate, $endDate)
    {
        // $endDate = date('Y-m-d', strtotime($endDate.' + 1 day'));
        //var_dump($endDate);die;
        //echo '<pre>';
        $connection = Yii::app()->secondaryDb;
        $orderSql = 'select l.vendor_id as vendor_id,  sum(l.price) as price from purchase_header as h left join purchase_line as l on h.id = l.purchase_id where h.delivery_date BETWEEN "' . $startDate . '" AND "' . $endDate . '" and h.status = "received" and l.price > 0 and l.received_qty > 0 group by l.vendor_id';
        
        $paymentSql = 'select vendor_id,payment_type, cheque_status , paid_amount as paid_amount from vendor_payments where status = 1 ';

        $command = $connection->createCommand($orderSql);
        $orderAmount = $command->queryAll();
        $command = $connection->createCommand($paymentSql);
        $paymentAmount = $command->queryAll();
        
        $order = array();
        $payment = array();
        foreach ($orderAmount as $key => $value) {
            if (!empty($value['price']))
                $order[$value['vendor_id']] = $value['price'];
        }
        foreach ($paymentAmount as $key => $value) {
            if (!empty($value['paid_amount'])) {
                if (!($value['payment_type'] == 'Cheque' && $value['cheque_status'] != 'Cleared')) {
                    if (array_key_exists($value['vendor_id'], $payment)) {
                        $payment[$value['vendor_id']] -= $value['paid_amount'];
                    } else {
                        $payment[$value['vendor_id']] = 0 - $value['paid_amount'];
                    }
                }
            }
        }
        //var_dump($order, $payment);die;
        foreach ($order as $key => $value) {
            if (array_key_exists($key, $payment)) {
                $order[$key] += $payment[$key];
            }
        }
        foreach ($payment as $key => $value) {
            if (!array_key_exists($key, $order)) {
                $order[$key] = $value;
            }
        }
        return $order;
    }

    public function getVendorPayableAmount($startDate, $endDate, $vendor_id, $paymentEndDate)
    {
        //$endDate = date('Y-m-d', strtotime($endDate.' + 1 day'));
        //$paymentEndDate = date('Y-m-d', strtotime($paymentEndDate.' + 1 day'));
        $connection = Yii::app()->secondaryDb;
        $orderSql = 'select l.vendor_id as vendor_id,  sum(l.price) as price, h.id from purchase_header as h left join purchase_line as l on h.id = l.purchase_id where h.delivery_date BETWEEN "' . $startDate . '" AND "' . $endDate . '" and h.status = "received" and l.vendor_id = ' . $vendor_id . '  and l.price > 0 and l.received_qty > 0'; //group by h.delivery_date order by h.delivery_date';
        $paymentSql = 'select vendor_id,payment_type, cheque_status , paid_amount as paid_amount from vendor_payments where date between "' . $startDate . '" and "' . $paymentEndDate . '" and status = 1 and vendor_id = ' . $vendor_id;
        $command = $connection->createCommand($orderSql);
        $orderAmount = $command->queryAll();
        $command = $connection->createCommand($paymentSql);
        $paymentAmount = $command->queryAll();
        //     if($vendor_id == 1){
        //     var_dump($orderAmount);
        //     var_dump($paymentAmount);die;
        // }
        $result = 0;
        foreach ($orderAmount as $key => $value) {
            $result += $value['price'];
        }
        foreach ($paymentAmount as $value) {
            if (!empty($value['paid_amount'])) {
                if (!($value['payment_type'] == 'Cheque' && $value['cheque_status'] != 'Cleared')) {
                    $result -= $value['paid_amount'];
                }
            }
        }
        return $result;
    }

    public function getLastDueDateFrom($startDate, $dueDate, $creditDays)
    {
        if (strtotime($startDate) >= strtotime($dueDate)) {
            return $dueDate;
        } else {
            while (strtotime($startDate) < strtotime($dueDate)) {
                $dueDate = date('Y-m-d', strtotime($dueDate . ' - ' . $creditDays . ' day'));
            }
            return $dueDate;
        }
    }

    public function getPayable($date, $initialPendingDate)
    {
        $criteria = new CDbCriteria;
        $criteria->select = 'id, credit_days, due_date, payment_days_range';
        $criteria->condition = 'status = 1';
        $vendors = Vendor::model()->findAll($criteria);
        $result = array();
        foreach ($vendors as $key => $value) {
            $lastDueDate = self::getLastDueDateFrom($date, $value['due_date'], $value['credit_days']);
            $startDate = date('Y-m-d', strtotime($lastDueDate . ' - ' . $value['credit_days'] . ' day'));
            $endDate = date('Y-m-d', strtotime($startDate . ' + ' . $value['payment_days_range'] . ' day'));
            $amount = self::getVendorPayableAmount($initialPendingDate, $endDate, $value['id'], $date);
            $result[$value['id']]['amount'] = $amount;
            $result[$value['id']]['dueDate'] = $lastDueDate;
            $result[$value['id']]['lastDueDate'] = date('Y-m-d', strtotime($lastDueDate . ' - ' . $value['payment_days_range'] . ' day'));
        }
        return $result;
    }

    public function getVendorOrderQuantity($vendorId)
    {
        $connection = Yii::app()->secondaryDb;
        $sql = 'select ph.id,sum(pl.received_qty) as received_qty, sum(pl.price) as price, ph.delivery_date from purchase_line as pl left join purchase_header as ph on pl.purchase_id = ph.id where ph.status = "received" and pl.received_qty > 0 and pl.price > 0 and pl.vendor_id = ' . $vendorId . ' group by ph.delivery_date order by ph.delivery_date';
        //$sql = 'select sum(pl.received_qty) as received_qty, sum(pl.price) as price, ph.delivery_date from purchase_line as pl left join purchase_header as ph on pl.purchase_id = ph.id where pl.status = "pending" and (pl.received_qty > 0 or pl.order_qty > 0) and pl.price > 0 and pl.vendor_id = '.$vendorId.' group by ph.delivery_date order by ph.delivery_date';
        //die($sql);
        $command = $connection->createCommand($sql);
        $orderedAmount = $command->queryAll();
        //var_dump($orderedAmount);die;
        return $orderedAmount;

    }

    public function getLastPendingDate($date, $initialPendingDate)
    {
        while (strtotime($date) > strtotime($initialPendingDate)) {
            $initialPendingDate = date('Y-m-d', strtotime($initialPendingDate . ' - 2 month'));
        }
        return $initialPendingDate;
    }

    public function getInitialPendingDate()
    {
        $connection = Yii::app()->db;
        $sql = 'select initial_pending_date from vendors limit 1';
        $command = $connection->createCommand($sql);
        return $command->queryScalar();
    }

    public function getAllVendorInitialPending($startDate)
    {
        $map = array();
        $connection = Yii::app()->db;
        $sql = 'select id from vendors where status = 1';
        $command = $connection->createCommand($sql);
        $ids = $command->queryAll();
        foreach ($ids as $key => $value) {
            $sql = 'select total_pending from vendor_log where vendor_id = ' . $value['id'] . ' and base_date = "' . $startDate . '" order by id desc limit 1';
            $command = $connection->createCommand($sql);
            $amount = $command->queryScalar();
            $map[$value['id']] = $amount;
        }
        return $map;
    }

    public function getVendorLastPaymentDetails()
    {
        $map = array();
        $sql = 'select vp1.vendor_id, vp1.date, sum(vp1.paid_amount) as paid_amount from vendor_payments as vp1 inner join (select max(date) as date, vendor_id from vendor_payments group by vendor_id ) as vp2 on (vp2.date = vp1.date and vp1.vendor_id = vp2.vendor_id and vp1.status = 1) group by vp1.vendor_id, vp1.date';
        $connection = Yii::app()->secondaryDb;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        //var_dump($result);
        foreach ($result as $key => $value) {
            $map[$value['vendor_id']]['date'] = $value['date'];
            $map[$value['vendor_id']]['amount'] = $value['paid_amount'];
        }
        return $map;
    }

    public function getAllVendorsPriceMap()
    {
        $sql = 'select base_product_id, vendor_id, price from vendor_product_mapping';
        $connection = Yii::app()->db;
        $command = $connection->createCommand($sql);
        $data = $command->queryAll();
        $result = array();
        foreach ($data as $key => $value) {
            $result[$value['vendor_id']][$value['base_product_id']] = $value['price'];
        }
        //var_dump($result);die;
        return $result;
    }

    public function getLineByPurchasId($vendorId, $purchaseId)
    {
        $connection = Yii::app()->secondaryDb;
        $sql = 'select pl.*, bp.title as product_name , bp.parent_id from purchase_line as pl left join purchase_header as ph on ph.id = pl.purchase_id
        left join cb_dev_groots.base_product as bp on bp.base_product_id = pl.base_product_id where pl.purchase_id = ' . $purchaseId . ' and pl.vendor_id =' . $vendorId . ' and ph.status = "received" and pl.received_qty > 0 and (bp.parent_id > 0 or bp.parent_id is null) ';
        //var_dump($sql);
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        return $result;
    }

    public function getVendorProductByIds($prodIds, $vendorId)
    {
        $connection = Yii::app()->db;
        $sql = 'select id, base_product_id, price from vendor_product_mapping where vendor_id = ' . $vendorId . ' and  base_product_id in (' . $prodIds . ')';
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $map = array();
        if (isset($result)) {
            foreach ($result as $key => $value) {
                $map[$value['base_product_id']] = $value;
            }
        }
        return $map;
    }

    public function getVendorSupplyTypeDropdown()
    {
        $connection = Yii::app()->db;
        $result = Utility::get_enum_values($connection, 'vendors', 'supplier_type');
        $vendorTypes = array();
        foreach ($result as $key => $value) {
            $vendorTypes[$value['value']] = $value['value'];
        }
        return $vendorTypes;
    }

    public function getVendorInitialPendingAmount($vendor_id)
    {
        $connection = Yii::app()->db;
        $sql = 'select initial_pending_amount from vendors where id = ' . $vendor_id;
        $command = $connection->createCommand($sql);
        $amount = $command->queryScalar();
        return (isset($amount)) ? $amount : 0;
    }

    public function downloadLedger($dataArray)
    {
        $fileName = 'VendorPayment.csv';
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

    public function getLedgerData($vendor_id)
    {
        //echo '<pre>';
        $connection = Yii::app()->secondaryDb;
        $base_date = self::getInitialPendingDate();
        $orderQuery = 'select ph.id,ph.labour_cost ,GROUP_CONCAT(DISTINCT pl.urd_number order by pl.urd_number asc SEPARATOR ",") as urd_number,
                        sum(pl.order_qty) as procured_qty, sum(pl.price) as price, ph.delivery_date as date, "Order" as type
                          from purchase_line as pl left join purchase_header as ph on pl.purchase_id = ph.id 
                          where ph.delivery_date > "' . $base_date . '" and ph.status = "received" and pl.order_qty > 0 and pl.price > 0 and pl.vendor_id = ' . $vendor_id . ' 
                          group by ph.delivery_date order by ph.delivery_date';
        //var_dump($orderQuery);die;
        $paymentsQuery = 'select *, "Payment" as type from vendor_payments where vendor_id = ' . $vendor_id . ' and status = 1 and date > "' . $base_date . '"';
        $command = $connection->createCommand($orderQuery);
        $orders = $command->queryAll();
        $command = $connection->createCommand($paymentsQuery);
        $payments = $command->queryAll();
        $ledgerData = array_merge_recursive($orders, $payments);
        $dataProvider = array();
        foreach ($ledgerData as $key => $value) {
            $type[$key] = $value['type'];
            $date[$key] = $value['date'];
        }
        if (!empty($ledgerData)) {
            array_multisort($date, SORT_ASC, $type, SORT_ASC, $ledgerData);
        }
        $outstanding = VendorDao::getVendorInitialPendingAmount($vendor_id);
        foreach ($ledgerData as $key => $value) {
            if ($value['type'] == 'Order') {
                $outstanding += $value['price'];
            } else if ($value['type'] == 'Payment') {
                if (!($value['payment_type'] == 'Cheque' && $value['cheque_status'] != 'Cleared')) {
                    $outstanding -= $value['paid_amount'];
                }
            }
            $value['outstanding'] = $outstanding;
            $ledgerData[$key] = $value;

        }
        $dataProvider = self::makeLedgerDataProvider($ledgerData, $vendor_id);
        //echo '<pre>';
        //var_dump($dataProvider);die;
        $result = array('data' => $dataProvider);
        $dataProvider = new CArrayDataProvider($dataProvider, array(
            'sort' => array(
                'attributes' => array('id', 'date', 'paid_amount', 'order_amount', 'order_quantity'),
            ), 'pagination' => array('pageSize' => 100)));
        $result['dataProvider'] = $dataProvider;
        return $result;

    }

    public function makeLedgerDataProvider($ledgerData, $vendor_id)
    {
        $dataProvider = array();
        foreach ($ledgerData as $ledgerRow) {
            $tmp = array();
            $tmp['id'] = $ledgerRow['id'];
            $tmp['type'] = $ledgerRow['type'];
            $tmp['date'] = $ledgerRow['date'];
            $tmp['urd'] = isset($ledgerRow['urd_number']) ? $ledgerRow['urd_number'] : null;
            $tmp['paid_amount'] = isset($ledgerRow['paid_amount']) ? $ledgerRow['paid_amount'] : null;
            $tmp['order_amount'] = isset($ledgerRow['price']) ? $ledgerRow['price'] : null;
            $tmp['order_quantity'] = isset($ledgerRow['procured_qty']) ? $ledgerRow['procured_qty'] : null;
            $tmp['paid_amount'] = isset($ledgerRow['paid_amount']) ? $ledgerRow['paid_amount'] : null;
            $tmp['outstanding'] = $ledgerRow['outstanding'];
            $tmp['payment_type'] = isset($ledgerRow['payment_type']) ? $ledgerRow['payment_type'] : null;
            $tmp['cheque_no'] = (isset($ledgerRow['cheque_no']) && $ledgerRow['payment_type'] == 'Cheque') ? $ledgerRow['cheque_no'] : null;
            $tmp['debit_no'] = (isset($ledgerRow['debit_no']) && $ledgerRow['payment_type'] == 'Debit Note') ? $ledgerRow['debit_no'] : null;
            $tmp['cheque_status'] = (isset($ledgerRow['cheque_status']) && $ledgerRow['payment_type'] == 'Cheque') ? $ledgerRow['cheque_status'] : null;
            $tmp['cheque_issue_date'] = (isset($ledgerRow['cheque_issue_date']) && $ledgerRow['payment_type'] == 'Cheque') ? $ledgerRow['cheque_issue_date'] : null;
            $tmp['cheque_name'] = (isset($ledgerRow['cheque_name']) && $ledgerRow['payment_type'] == 'Cheque') ? $ledgerRow['cheque_name'] : null;
            $tmp['is_cheque_reissued'] = (isset($ledgerRow['is_cheque_reissued']) && $ledgerRow['payment_type'] == 'Cheque') ? $ledgerRow['is_cheque_reissued'] : null;
            $tmp['reissue_ref_no'] = (isset($ledgerRow['reissue_ref_no']) && $ledgerRow['payment_type'] == 'Cheque') ? $ledgerRow['reissue_ref_no'] : null;
            $tmp['transaction_id'] = (isset($ledgerRow['transaction_id']) && $ledgerRow['payment_type'] == 'NetBanking') ? $ledgerRow['transaction_id'] : null;
            $tmp['receiving_acc_no'] = (isset($ledgerRow['receiving_acc_no']) && $ledgerRow['payment_type'] == 'NetBanking') ? $ledgerRow['receiving_acc_no'] : null;
            $tmp['bank_name'] = isset($ledgerRow['bank_name']) ? $ledgerRow['bank_name'] : null;
            $tmp['isfc_code'] = isset($ledgerRow['isfc_code']) ? $ledgerRow['isfc_code'] : null;
            $tmp['acc_holder_name'] = isset($ledgerRow['acc_holder_name']) ? $ledgerRow['acc_holder_name'] : null;
            $tmp['comment'] = isset($ledgerRow['comment']) ? $ledgerRow['comment'] : null;
            $tmp['status'] = isset($ledgerRow['status']) ? $ledgerRow['status'] : null;
            $tmp['labour_cost'] = ($vendor_id == 4 && isset($ledgerRow['labour_cost']) && !empty($ledgerRow['labour_cost'])) ? $ledgerRow['labour_cost'] : '';
            $tmp['outstanding'] += ($tmp['labour_cost'] != '') ? $tmp['labour_cost'] : 0;
            array_push($dataProvider, $tmp);
        }
        return $dataProvider;
    }

    public function getAllVendorFromPurchase($id)
    {
        $connection = Yii::app()->secondaryDb;
        $sql = 'select distinct vendor_id from purchase_line where purchase_id = ' . $id . ' and vendor_id is not null and vendor_id != 0';
        //var_dump($sql);die;
        $command = $connection->createCommand($sql);
        return $command->queryAll();
    }

    public function downloadVendorMasterReport($w_id)
    {
        $connection = Yii::app()->db;
        $sql = 'select v.id, v.name, v.bussiness_name, v.vendor_code, v.VAT_number, v.email, v.mobile, v.telephone, v.address, 
                v.pincode, v.owner_phone, v.owner_email, v.settlement_days, v.time_of_delivery, v.date_of_onboarding, v.city, 
                v.state, v.image, v.image_url, v.website, v.contact_person1, v.contact_person2, v.status, v.credit_limit, 
                v.created_date, v.updated_at,wa.name as `warehouse`, v.initial_pending_amount, v.payment_start_date, ge.name as `procurement executive`, 
                v.vendor_type, v.credit_days, v.due_date, v.payment_days_range, v.initial_pending_date, v.supplier_type, v.account_holder_name, 
                v.bank_account_no, v.account_type, v.bank_name, v.branch_name, v.isfc_code from vendors as v left join groots_employee as ge
                on ge.id = v.proc_exec_id left join warehouses as wa on wa.id = v.allocated_warehouse_id where allocated_warehouse_id = ' . $w_id;
        //var_dump($sql);die;
        $command = $connection->createCommand($sql);
        $dataArray = $command->queryAll();
        $fileName = 'VendorMaster.csv';
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

    public function downloadCreditReport($payable, $totalPendingMap, $initialPendingMap, $lastPaymentDetails, $endDate)
    {
        $w_id = '';
        if (isset(Yii::app()->session['w_id']) && !empty(Yii::app()->session['w_id'])) {
            $w_id = Yii::app()->session['w_id'];
        }
        $connection = Yii::app()->db;
        $sql = 'select id, name,bussiness_name, total_pending_amount, vendor_type, due_date, credit_days, credit_limit, initial_pending_amount from vendors where status = 1 and allocated_warehouse_id = ' . $w_id;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $dataArray = array();
        //echo '<pre>';
        //var_dump($result);
        foreach ($result as $vendor) {
            $temp['ID'] = $vendor['id'];
            $temp['Name'] = $vendor['name'];
            $temp['Bussiness Name'] = $vendor['bussiness_name'];
            $temp['Vendor Type'] = $vendor['vendor_type'];
            $currentPayable = $payable[$vendor['id']]['amount'] + $initialPendingMap[$vendor['id']] - $vendor['credit_limit'];
            $temp['Amount Payable'] = ($currentPayable > 0) ? $currentPayable : 0;
            $temp['Total Pending'] = (array_key_exists($vendor['id'], $initialPendingMap)) ? $initialPendingMap[$vendor['id']] : 0;
            $temp['Total Pending'] += (array_key_exists($vendor['id'], $totalPendingMap)) ? $totalPendingMap[$vendor['id']] : 0;
            $temp['Payment Due Date'] = $payable[$vendor['id']]['dueDate'];
            $temp['Last Paid On'] = (array_key_exists($vendor['id'], $lastPaymentDetails)) ? $lastPaymentDetails[$vendor['id']]['date'] : 'NA';
            $temp['Last Paid Amount'] = (array_key_exists($vendor['id'], $lastPaymentDetails)) ? $lastPaymentDetails[$vendor['id']]['amount'] : 'NA';
            array_push($dataArray, $temp);
        }
        //var_dump($dataArray);die;
        $fileName = 'CreditManagement' . $endDate . '.csv';
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

    public function allVendorDropdown()
    {
        $connection = Yii::app()->db;
        $sql = 'select id, bussiness_name from vendors where status = 1 and allocated_warehouse_id = ' . Yii::app()->session['w_id'];
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $array = array(0 => 'Select vendor');
        foreach ($result as $vendor) {
            $array[$vendor['id']] = $vendor['bussiness_name'];
        }
        return $array;
    }

    public function addUploadFileData($result, $vendor_id, $post, $file)
    {
        $connection = Yii::app()->db;
        $sql = 'insert into vendor_upload (id , vendor_id,bucket,file_tag, file_name,file_size,file_link,file_type,date, status, created_at,
                updated_at,updated_by) values(null, ' . $vendor_id . ', "' . VENDOR_UPLOAD_BUCKET . '","' . $post["fileTag"] . '","' . $file["file"]["name"] . '"
                ,' . ($file["file"]["size"] / 1000) . ',"' . $result["@metadata"]["effectiveUri"] . '","' . $post["file_type"] . '","' . $post["date"] . '",1,NOW(),NOW(),
                ' . Yii::app()->user->id . ')';
        //die($sql);
        $command = $connection->createCommand($sql);
        $rowCount = $command->execute();
        //$command->queryAll();
    }

    public function isLegitUpload($post)
    {
        $message = '';
        if (empty($post['date'])) {
            $message = 'Plaese Enter Date';
            return array('isLegit' => false, 'message' => $message);
        }
        if (empty($post['fileTag'])) {
            $message = 'Plaese Enter Some File Tag';
            return array('isLegit' => false, 'message' => $message);
        }
        if (empty($post['vendor_id'])) {
            $message = 'Plaese Select a vendor';
            return array('isLegit' => false, 'message' => $message);
        }
        if (empty($post['file_type'])) {
            $message = 'Plaese Select File Type ';
            return array('isLegit' => false, 'message' => $message);
        }
        return array('isLegit' => true, 'message' => $message);
    }

    public function checkFileTypes($fileClass, $fileType)
    {
        $array = array('matched' => false, 'message' => '');
        $file_types = array();
        $connection = Yii::app()->db;
        $sql = 'select file_type from permitted_file_types where file_class = "' . $fileClass . '"';
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        foreach ($result as $value) {
            array_push($file_types, $value['file_type']);
        }
        $file_types = implode(',', $file_types);
        foreach ($result as $value) {
            if ($value['file_type'] == $fileType) {
                $array['matched'] = true;
                return $array;
            }
        }

        $array['message'] = 'Only ' . $file_types . ' formats are allowed in ' . $fileClass . ' Category';
        return $array;
    }

    public function fileTypesDd()
    {
        $array = array();
        $connetion = Yii::app()->db;
        $sql = 'select distinct file_class from permitted_file_types';
        $command = $connetion->createCommand($sql);
        $result = $command->queryAll();
        foreach ($result as $value) {
            $array[$value['file_class']] = $value['file_class'];
        }
        $arrray[0] = 'Select A File';
        return $array;
    }


/*this is the new function to calculate totalprending amount for each vendor : old one is left untouched;
Just not using that in creditmanagaement anymore; this function is similar in flow to getLedgerData in vendorledger controller*/
    public function newGetAllVendorPayableAmount($startDate, $endDate)
    {
        // $endDate = date('Y-m-d', strtotime($endDate.' + 1 day'));
        //var_dump($endDate);die;
        //echo '<pre>';
        $connection = Yii::app()->secondaryDb;
        $vendorIdQuery = 'select id from cb_dev_groots.vendors where status = 1';
        $command = $connection->createCommand($vendorIdQuery);
        $vendorIds = $command->queryAll();
        $totalPendingMap = array();
        foreach($vendorIds as $value){
            $vendorId = $value['id'];
            $orderSql = 'select sum(pl.price) as price, ph.delivery_date as date from purchase_line as pl 
                          left join purchase_header as ph on pl.purchase_id = ph.id 
                          where ph.delivery_date BETWEEN "' . $startDate . '" AND "' . $endDate .'" and ph.status = "received" and pl.order_qty > 0 and pl.price > 0 and pl.vendor_id = '.$vendorId.' 
                          group by ph.delivery_date order by ph.delivery_date';
            $paymentSql = 'select *, "Payment" as type from vendor_payments where vendor_id = ' . $vendorId . ' and status = 1 and date > "' . $startDate . '"';
            $command = $connection->createCommand($orderSql);
            $orderAmount = $command->queryAll();
            $command = $connection->createCommand($paymentSql);
            $paymentAmount = $command->queryAll();
            $totalPendingMap[$vendorId] = 0;
            foreach ($orderAmount as $key => $value) {
                $totalPendingMap[$vendorId] += (!empty($value['price'])) ? $value['price'] : 0;
            }
            foreach ($paymentAmount as $key => $value) {
                if (!empty($value['paid_amount'])) {
                    if (!($value['payment_type'] == 'Cheque' && $value['cheque_status'] != 'Cleared')) {
                        $totalPendingMap[$vendorId] -= $value['paid_amount'];
                    }
                }
            }
        }
        
        return $totalPendingMap;
    }
}

?>