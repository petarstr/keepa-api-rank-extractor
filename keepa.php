<?php ob_start(); ?>
  <!DOCTYPE html>
<html>
    <head>
        <title>AMZ Reports</title>
        <link rel="icon" href="favicon.ico">
        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
        <!-- jQuery library -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
        <!-- Latest compiled JavaScript -->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

        <style>
        .progress {
            text-align: center;
            height: 30px;
        }
        .row {
            margin-top: 15%;
        }
        .buttons {
            margin-top: 2%;
        }

        #another-file {
            margin-top: 3%;
        }

        .progress-value {
            position: absolute;
            left: 0;
            right: 0;
            margin-top: 1%;
        }
        </style>

    </head>
    <body>

        <div class='row'>

            <div class="col-sm-4"></div>

            <div class="col-sm-4">
                 <div class="notice" align="center"> <h2>In progress..</h2> </div>
                 <div class="progress">
                     <div class="progress-holder">
                        <div class="progress-value">
                        </div>
                     </div>
                 </div>
                 <div class="buttons" align='center'>
                    <form method="get" action="stats.xlsx">
                        <button id='download-btn' type='submit' class='btn btn-info btn-lg' disabled>Download</button>
                    </form>
                        <button id='another-file' type='submit' onclick="window.open('http://amzreports.co/', '_self')" class='btn btn-default btn-lg' disabled>
                            Check another file
                        </button>
                </div>
            </div>
        </div>
    </body>
</html>

<?php


        if(!isset($_POST['submit'])){
            header("Location: http://amzreports.co");
        }

        // Excel File Upload
        if(isset($_FILES['file'])){
            $file = $_FILES['file'];

            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_size = $file['size'];
            $file_error = $file['error'];

            $file_ext = explode('.', $file_name);
            $file_ext = strtolower(end($file_ext));

            $allowed_ext = 'xlsx';

            if($file_ext == $allowed_ext){
                if($file_error === 0){
                    $file_name_new = uniqid('', true) . '.' . $file_ext;
                    $file_destination = 'uploads/' . $file_name_new;
                    if(move_uploaded_file($file_tmp, $file_destination)){
                        echo str_repeat(' ',1024*64);
                        flush(); 
                    }
                }
            }
        }
               
    


        ini_set('max_execution_time', 0);
        require_once 'vendor/autoload.php';
        require_once 'Excel/PHPExcel.php';


        use Keepa\API\Request;
        use Keepa\API\ResponseStatus;
        use Keepa\helper\CsvType;
        use Keepa\helper\CsvTypeWrapper;
        use Keepa\helper\KeepaTime;
        use Keepa\helper\ProductAnalyzer;
        use Keepa\helper\ProductType;
        use Keepa\KeepaAPI;
        use Keepa\objects\AmazonLocale;

        // Load product IDs from excel file to array
        function loadFile(){
            GLOBAL $file_destination;
            $fileName = $file_destination;
            
            $objPHPExcel = PHPExcel_IOFactory::load($fileName);
            $worksheet = $objPHPExcel->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();
            $highestColumnLetters = $worksheet->getHighestColumn();
            $highestColumn = PHPExcel_Cell::columnIndexFromString($highestColumnLetters); 

            $productArray = array();

            for($i = 1; $i <= $highestRow; $i++){
                $element = $worksheet->getCellByColumnAndRow('A', $i)->getValue();

                if(is_string($element)){
                    $productArray[$i] = $element;
                }
            }
            unlink($file_destination);
            
            return $productArray;
        }

        // Format Rank
        function separate_rank($raw_rank){
            if($raw_rank == -1 || $raw_rank == null){
                $rank = "Not Available";
            } else {
                $rank = number_format($raw_rank);                
            }
            return $rank;
        }

        // Send Request
        function getData($ASIN, $half_year_ago, $current_time){
            GLOBAL $api, $activeSheet, $row_no;
            
            if($ASIN != ''){ 
                $r = Request::getProductRequest(AmazonLocale::US, false, $half_year_ago, $current_time, 0, false, [$ASIN]);

                try{
                    $response = $api->sendRequestWithRetry($r);
                    process_response($response, $ASIN);
                } catch (Exception $e){
                    $activeSheet->getCell('A'.$row_no)->setValue($ASIN);
                    $activeSheet->getCell('B'.$row_no)->setValue('ASIN not available');
                    $row_no++;
                }          
            }
        } 

        // Process Response
        function process_response($response, $ASIN){
            GLOBAL $activeSheet, $row_no, $half_year_ago, $current_time;
                switch ($response->status) {
                    case ResponseStatus::OK:
                        // Iterate over received product information
                        foreach ($response->products as $product){
                            if ($product->productType == ProductType::STANDARD || $product->productType == ProductType::DOWNLOADABLE) {
               
                            $stat_obj = $product->stats;

                            // Get Minimum Rank
                            $min = $stat_obj->min;
                            $min_rank_raw = $min[3][1];
                            $min_rank = separate_rank($min_rank_raw);

                            // Get Maximum Rank
                            $max = $stat_obj->max;
                            $max_rank_raw = $max[3][1];
                            $max_rank = separate_rank($max_rank_raw);

                            // Get Average Rank - Period depends on Stat parameter in the request
                            $avg = $stat_obj->avg;
                            $avg_rank_raw = $avg[3];
                            $avg_rank = separate_rank($avg_rank_raw);

                            // Get Average Rank in past 30 days
                            $avg30 = $stat_obj->avg30;
                            $avg30_rank_raw = $avg30[3];
                            $avg30_rank = separate_rank($avg30_rank_raw);

                            // Get Average Rank in past 90 days
                            $avg90 = $stat_obj->avg90;
                            $avg90_rank_raw = $avg90[3];
                            $avg90_rank = separate_rank($avg90_rank_raw);

                            //Get Current Rank - Since last API update
                            $current = $stat_obj->current;
                            $current_rank_raw = $current[3];
                            $current_rank = separate_rank($current_rank_raw);

                            // Insert values into a new excel file
                            $activeSheet->getCell('A'.$row_no)->setValue($ASIN);
                            $activeSheet->getCell('B'.$row_no)->setValue($current_rank);
                            $activeSheet->getCell('C'.$row_no)->setValue($avg30_rank);
                            $activeSheet->getCell('D'.$row_no)->setValue($avg90_rank);
                            $activeSheet->getCell('E'.$row_no)->setValue($avg_rank);                 
                            $activeSheet->getCell('F'.$row_no)->setValue($min_rank);
                            $activeSheet->getCell('G'.$row_no)->setValue($max_rank);
                            
                            $row_no++;
                            
                            } else {
                                $activeSheet->getCell('A'.$row_no)->setValue($ASIN);
                                $activeSheet->getCell('B'.$row_no)->setValue('Data Not Available');
                                
                                $row_no++;
                                sleep(3);
                            }
                        }        
                        break;

                    case ResponseStatus::PAYMENT_REQUIRED:
                            $activeSheet->getCell('A2')->setValue("PAYMENT REQUIRED");
                            break;

                    case ResponseStatus::REQUEST_FAILED:
                            $activeSheet->getCell('B'.$row_no)->setValue("Request Failed");
                            $row_no++;
                            break;

                    case ResponseStatus::FAIL:
                            $activeSheet->getCell('B'.$row_no)->setValue("Request Failed");
                            $row_no++;
                            break;    

                    case ResponseStatus::REQUEST_REJECTED:
                            $activeSheet->getCell('B'.$row_no)->setValue("Request Rejected");
                            $row_no++;
                            break; 

                    case ResponseStatus::NOT_ENOUGH_TOKEN:
                            sleep(1);
                            getData($ASIN, $half_year_ago, $current_time);
                            break;  

                    case ResponseStatus::METHOD_NOT_ALLOWED:
                            $activeSheet->getCell('B'.$row_no)->setValue("Not Allowed");
                            $row_no++;
                            break; 

                    default:
                        getData($ASIN, $half_year_ago, $current_time);
                }

            }  

       

        // Prepare the past year period that will be used as stats parameter in the request. Client requested to be past 6 months.
        $current_time_raw = time();
        $half_year_ago_raw = strtotime("-6 month", $current_time_raw);

        $current_time = date("Y-m-d", $current_time_raw);
        $half_year_ago = date("Y-m-d", $half_year_ago_raw);
 

        //Load excell file with ASINs
        $productArray = loadFile();

        //Prepare empty excell file
        $objPHPExcel1 = new PHPExcel();
        $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel1);

        //Insert Column titles
        $activeSheet = $objPHPExcel1->getActiveSheet();
        $activeSheet->getCell('A1')->setValue('ASIN');
        $activeSheet->getCell('B1')->setValue('Current Rank');
        $activeSheet->getCell('C1')->setValue('AVG Rank 30');
        $activeSheet->getCell('D1')->setValue('AVG Rank 90');
        $activeSheet->getCell('E1')->setValue('AVG Rank 180');
        $activeSheet->getCell('F1')->setValue('Minimum Rank');
        $activeSheet->getCell('G1')->setValue('Maximum Rank');
        $activeSheet->getStyle('A1:G1')->getAlignment()->setWrapText(true);

        //Keepa API key required for this step
        $api = new KeepaAPI("key");

        $row_no = 2;
        $current = 0;

        // Progress Bar
        foreach($productArray as $ASIN){
    
            $current++;
            $total = count($productArray);
            $show = round($current/$total * 100, 0) . "%";  
            echo str_repeat(' ',1024*64);
            ob_flush();  
            getData($ASIN, $half_year_ago, $current_time);

            echo"
                    <script language='javascript'>
                    document.getElementsByClassName('progress')[0].innerHTML = '<div class=\'progress-bar progress-bar-striped active font\' aria-valuenow=\'70\' aria-valuemin=\'0\' aria-valuemax=\'50\' style=\'width:".$show."\'></div><div class=\'progress-value\'>".$show."</div>';
                    </script>";       
        }

            echo"
                    <script language='javascript'>
                    document.getElementsByClassName('notice')[0].innerHTML = '<h2> Your file is ready </h2>';
                    document.getElementById('download-btn').disabled = false;
                    document.getElementById('another-file').disabled = false;
                    </script>";            
        
        // Save file with results
        $objWriter->save("stats.xlsx");



?>

