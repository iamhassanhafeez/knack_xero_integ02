<?php



// =============================== Knack Configuration =======================
$JobCardTableEndPoint = 'https://api.knack.com/v1/objects/object_3/records';
$InvoiceTrackerTableEndPoint = 'https://api.knack.com/v1/objects/object_21/records';
$CustomersTableEndPoint = 'https://api.knack.com/v1/objects/object_1/records';
$ServLineItemsTableEndPoint = 'https://api.knack.com/v1/objects/object_10/records';
$ProdLineItemsTableEndPoint = 'https://api.knack.com/v1/objects/object_8/records';
$KnackSysAuditLogEndPoint = 'https://api.knack.com/v1/objects/object_20/records';
$XeroSetupTableEndPoint = 'https://api.knack.com/v1/objects/object_39/records';



$api_key = 'XXXXXXXXXXXXXXXXXXXXXXXXXX';
$app_id = 'XXXXXXXXXXXXXXXXXXXXXXXXXXX';


$job_id = "66b062e489b9350028eb3488";
$XeAccNo = "xe2200";
$final_line_items = [];

//=============== Fetch product line items from knack
echo '<br/># Fetching associated product line items.';
$prod_line_items = read_product_line_items($ProdLineItemsTableEndPoint, $job_id, $app_id, $api_key); 

// Debugging: Print product line items
echo "<pre>";
print_r($prod_line_items);
echo "</pre>";

if (isset($prod_line_items['records']) && count($prod_line_items['records']) > 0) {
    foreach($prod_line_items['records'] as $prod) {
        $final_line_items[] = [
            'Description' => strip_tags($prod['field_85']), 
            'Quantity' => $prod['field_54'],
            'UnitAmount' => convertCurrency($prod['field_55']),
            'AccountCode' => $XeAccNo,  
            'TaxType' => 'NONE',  
            'LineAmount' => convertCurrency($prod['field_57']) ?? $prod['field_54'] * convertCurrency($prod['field_55'])  
        ];
    }
} else {
    echo "<br/>No product line items found!";
}

//=============== Fetch service line items
echo '<br/># Fetching associated service line items.';
$service_line_items = read_service_line_items($ServLineItemsTableEndPoint, $job_id, $app_id, $api_key);

// Debugging: Print service line items
echo "<pre>";
print_r($service_line_items);
echo "</pre>";

if (isset($service_line_items['records']) && count($service_line_items['records']) > 0) {
    foreach($service_line_items['records'] as $serv) {
        $final_line_items[] = [
            'Description' => "Service - ".strip_tags($serv['field_77']), 
            'Quantity' => $serv['field_79'],
            'UnitAmount' => convertCurrency($serv['field_80']),
            'AccountCode' => $XeAccNo,  
            'TaxType' => 'NONE',  
            'LineAmount' => convertCurrency($serv['field_84']) ?? $serv['field_79'] * convertCurrency($serv['field_80'])  
        ];
    }
} else {
    echo "<br/>No service line items found!";
}

echo "<pre>";
print_r($final_line_items);
echo "</pre>";


// Function to log messages to a file
function logMessage($message)
{
    $logFile = 'app-logs.log'; // Path to your log file
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

  function convertCurrency($amount) {
    // Remove any non-numeric characters except the decimal point
    $cleanAmount = preg_replace('/[^\d.]/', '', $amount);
    return number_format((float)$cleanAmount, 2, '.', '');
}


function read_product_line_items($ProdLineItemsTableEndPoint, $jobCardId, $app_id, $api_key) {
    // Filter criteria with dynamic $jobCardNumber
    echo "job card id is:". $jobCardId;
    $filter_criteria = [
        'match' => 'and',
        'rules' => [
            [
                'field' => 'field_58',  // Connection field for job card number
                'operator' => 'is',
                'value' => $jobCardId,  // Use the job card rec id
            ],
        ],
    ];

    // Initialize cURL
    $ch = curl_init();

    // Encode the filter criteria and append it as a query parameter
    $url = $ProdLineItemsTableEndPoint . '?filters=' . urlencode(json_encode($filter_criteria));

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Knack-Application-Id: ' . $app_id,
        'X-Knack-REST-API-Key: ' . $api_key,
        'Content-Type: application/json',
    ]);

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
    } else {
        // Check HTTP response code
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 200) {
            // Decode the JSON response
            $response_data = json_decode($response, true); // Assuming the response is in JSON format

            // echo '<pre>';
            // print_r($response_data);
            // echo '</pre>';
            $message = "<br/>Product line items found.";
            logMessage($message);
            echo ("<br/>> Product line items found.");
        } else {
            echo "Request failed with HTTP status code: $http_code";
        }
    }

    // Close cURL
    curl_close($ch);

    // Return the response data for further use if needed
    return $response_data;
}


function read_service_line_items($ServiceLineItemsTableEndPoint, $jobCardNumber, $app_id, $api_key){
    echo "job card id is:". $jobCardNumber;
    // Filter criteria 
    $filter_criteria = [
       'match' => 'and',
       'rules' => [
           [
               'field' => 'field_81',  // job card number
               'operator' => 'is',
               'value' =>  $jobCardNumber,
           ],
       ],
   ];

   // Initialize cURL
   $ch = curl_init();

   // Set cURL options
   $url = $ServiceLineItemsTableEndPoint . '?filters=' . urlencode(json_encode($filter_criteria));
   curl_setopt($ch, CURLOPT_URL, $url);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
   curl_setopt($ch, CURLOPT_HTTPHEADER, [
       'X-Knack-Application-Id: ' . $app_id,
       'X-Knack-REST-API-Key: ' . $api_key,
       'Content-Type: application/json',
   ]);

   // Execute the request
   $response = curl_exec($ch);

   // Check for errors
   if (curl_errno($ch)) {
       echo 'Error: ' . curl_error($ch);
   } else {
       // Check HTTP response code
       $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
       if ($http_code == 200) {
           // Decode the JSON response
           $response_data = json_decode($response, true); // Assuming the response is in JSON format

        //    echo '<pre>';
        //    print_r($response_data);
        //    echo '</pre>';
           $message = "<br/>Service line items.";
           logMessage($message);
           echo ("<br/>> Service line items found.");
       } else {
           echo "Request failed with HTTP status code: $http_code";
       }
   }

   // Close cURL
   curl_close($ch);

   return $response_data;
   
}













?>