<?php
$username = "root";
$password = "root";
$localhost = "localhost";
$database = "cb_dev_groots";
$connection = mysql_connect($localhost,$username, $password);
$sql = "select due_date, collection_frequency, status, id from cb_dev_groots.retailer order by id asc";
echo $sql."\n";
if ($result=mysql_query($sql))
  {
    //echo date("t");

      $yesterday = date('Y-m-d');
      $yesterday = date('Y-m-d', strtotime($yesterday.'-1 days'));

    while($rowinfo=mysql_fetch_array($result)){

        $date = $rowinfo['due_date'];

        if($yesterday == $date) {

            $month = substr($date, 5, 2);
            $day = substr($date, 8, 2);
            $year = substr($date, 0, 4);
            if ($rowinfo['collection_frequency'] == 'daily')
                $date = date('Y-m-d', strtotime($date . '+1 days'));

            else if ($rowinfo['collection_frequency'] == 'weekly')
                $date = date('Y-m-d', strtotime($date . '+7 days'));

            else if ($rowinfo['collection_frequency'] == 'fortnight') {
                if ($day == '01')
                    $date = substr_replace($date, '16', 8, 2);

                else {
                    $date = date('Y-m-d', strtotime($date . '+1 month'));
                    $date = substr_replace($date, '01', 8, 2);
                }
            } else if ($rowinfo['collection_frequency'] == 'monthly') {
                $date = date('Y-m-d', strtotime($date . '+1 month'));
            } else
                $date = date('Y-m-d', strtotime($date . '+45 days'));


            //echo $date;
            // echo "   ";
            // echo $rowinfo['due_date'];
            // echo "         ";
            if ($rowinfo['status'] == 1 ){
                $query = "update cb_dev_groots.retailer set last_due_date = due_date , due_date = '" . $date . "'" . " where id = '" . $rowinfo['id'] . "';";
                mysql_query($query);
                echo $query."\n";
            }
        }

    }
}
else{
	echo "Connection failed!.....";
}
?>