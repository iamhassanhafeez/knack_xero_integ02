<?php
require __DIR__ . '/vendor/autoload.php';

session_start();

use GuzzleHttp\Client;

//============ Xero Config ==================
$clientId = 'DB8962151A3D4C339A0D4B1E12712771';
$clientSecret = 'hnXTTDTWyKi4Crhw2TEPnxmyP2qX92TH-HoCadvanaVX9w-P';
// old $redirectUri = 'http://localhost/xero-app-new/index.php';
$redirectUri = 'http://localhost/knack_xero_integration/Xero-invoice-creator.php';

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => $clientId,
    'clientSecret'            => $clientSecret,
    'redirectUri'             => $redirectUri,
    'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
    'urlAccessToken'          => 'https://identity.xero.com/connect/token',
    'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Invoices'
]);


// =============================== Knack Configuration =======================
$JobCardTableEndPoint = 'https://api.knack.com/v1/objects/object_3/records';
$InvoiceTrackerTableEndPoint = 'https://api.knack.com/v1/objects/object_21/records';
$CustomersTableEndPoint = 'https://api.knack.com/v1/objects/object_1/records';
$ServLineItemsTableEndPoint = 'https://api.knack.com/v1/objects/object_10/records';
$ProdLineItemsTableEndPoint = 'https://api.knack.com/v1/objects/object_8/records';
$KnackSysAuditLogEndPoint = 'https://api.knack.com/v1/objects/object_20/records';


$api_key = '5731568a-75ed-4a6e-b906-7c3cda415405';
$app_id = '64ec0e7df4070c0028ff4a07';


// Let's establish a connection with Xero first

// If we don't have an authorization code then get one
if (!isset($_GET['code'])) {

    $options = [
        'scope' => ['openid email profile offline_access accounting.transactions accounting.settings']
    ];

    // Fetch the authorization URL from the provider; this returns the
    // urlAuthorize option and generates and applies any necessary parameters (e.g. state).
    $authorizationUrl = $provider->getAuthorizationUrl($options);

    // Get the state generated for you and store it to the session.
    $_SESSION['oauth2state'] = $provider->getState();

    // Redirect the user to the authorization URL.
    header('Location: ' . $authorizationUrl);
    exit();

    // Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Invalid state');

    // Redirect back from Xero with code in query string param
} else {

    try {
        // Try to get an access token using the authorization code grant.
        $accessToken = $provider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);

        // Log the successful token retrieval
      //  logMessage("Access token retrieved successfully.");

        // We have an access token, which we may use in authenticated requests 
        // Retrieve the array of connected orgs and their tenant ids.      
        $options['headers']['Accept'] = 'application/json';
        $connectionsResponse = $provider->getAuthenticatedRequest(
            'GET',
            'https://api.xero.com/Connections',
            $accessToken->getToken(),
            $options
        );

        $xeroTenantIdArray = $provider->getParsedResponse($connectionsResponse);


        $tenantID = $xeroTenantIdArray[0]['tenantId'];

        // Log the tenant ID
      //  logMessage("Xero tenant ID retrieved: $tenantID");

        echo '<h3 class="success" style="color:#ff0000; cursor:pointer;">>> Connected Successfully - Click to toggle info visibility.</h1>';
        echo '<div class="raw_connection_info" style="color:#c40233; display:none;">';
        echo "access token: " . $accessToken->getToken() . "<hr>";
        echo "refresh token: " . $accessToken->getRefreshToken() . "<hr>";
        echo "xero tenant id: " . $tenantID . "<hr>";

        // The provider provides a way to get an authenticated API request for
        // the service, using the access token; 
        // the xero-tentant-id header is required
        // the accept header can be either 'application/json' or 'application/xml'
        $options['headers']['xero-tenant-id'] = $tenantID;
        $options['headers']['Accept'] = 'application/json';

        $request = $provider->getAuthenticatedRequest(
            'GET',
            'https://api.xero.com/api.xro/2.0/Organisation',
            $accessToken,
            $options
        );

        echo 'Organisation details:<br><textarea width: "300px"  height: 150px; row="50" cols="40">';
        var_export($provider->getParsedResponse($request));
        echo '</textarea>';
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {

        // Log the exception message
      //  logMessage("Error retrieving access token or user details: " . $e->getMessage());

        // Failed to get the access token or user details.
        exit($e->getMessage());
    }

    echo "</div>";
}
    


$xfinal_line_items = [
    "Id" => "c82e685b-8c64-43ee-8b27-996c7dc743f5",
    "Status" => "OK",
    "ProviderName" => "API Explorer",
    "DateTimeUTC" => "/Date(1732871175237)/",
    "Invoices" => [
        [
            "Type" => "ACCREC",
            "InvoiceID" => "14e52695-7c90-413f-b6ea-e32a79145665",
            "InvoiceNumber" => "INV-0001",
            "Reference" => "",
            "Payments" => [],
            "CreditNotes" => [],
            "Prepayments" => [],
            "Overpayments" => [],
            "AmountDue" => 456,
            "AmountPaid" => 0,
            "AmountCredited" => 0,
            "CurrencyRate" => 1,
            "IsDiscounted" => false,
            "HasAttachments" => false,
            "InvoiceAddresses" => [],
            "HasErrors" => false,
            "InvoicePaymentServices" => [],
            "Contact" => [
                "ContactID" => "170473c3-c21c-4016-af7a-4bb32008664c",
                "Name" => "Hassan hafeez",
                "Addresses" => [],
                "Phones" => [],
                "ContactGroups" => [],
                "ContactPersons" => [],
                "HasValidationErrors" => false
            ],
            "DateString" => "2024-11-29T00:00:00",
            "Date" => "/Date(1732838400000+0000)/",
            "DueDateString" => "2024-12-06T00:00:00",
            "DueDate" => "/Date(1733443200000+0000)/",
            "BrandingThemeID" => "38523938-df80-40e8-b42c-cc2bae4e961d",
            "Status" => "DRAFT",
            "LineAmountTypes" => "NoTax",
            "LineItems" => [],
            "SubTotal" => 456,
            "TotalTax" => 0,
            "Total" => 456,
            "UpdatedDateUTC" => "/Date(1732871161967+0000)/",
            "CurrencyCode" => "NZD"
        ]
    ]
];



//use Carbon\Carbon;



///Call functionality
xero_invoice_tracker_in_knack($InvoiceTrackerTableEndPoint, $CustomersTableEndPoint, $JobCardTableEndPoint, $ProdLineItemsTableEndPoint, $ServLineItemsTableEndPoint, $api_key, $app_id, $accessToken, $tenantID, $provider, $KnackSysAuditLogEndPoint);

//=========================>>>>>>>>>>> DEFINE FUNCTIONALITY <<<<<<<<<<<<============================================
//==================================================================================================================

// Function to log messages to a file
function logMessage($message)
{
    $logFile = 'app-logs.log'; // Path to your log file
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

function xero_invoice_tracker_in_knack($InvoiceTrackerTableEndPoint, $CustomersTableEndPoint, $JobCardTableEndPoint, $ProdLineItemsTableEndPoint, $ServLineItemsTableEndPoint, $api_key, $app_id, $accessToken, $tenantID, $provider, $KnackSysAuditLogEndPoint){
    $all_records = [];
    $page = 1;
    $per_page = 100; // You can adjust this if needed (max 100 per page)

    // Filter criteria 
    $filter_criteria = [
        'match' => 'or',
        'rules' => [
            [
                'field' => 'field_228',         // Xero Invoice Creation Start
                'operator' => 'is blank'
            ]
        ],
    ];

    do {
        // Initialize cURL
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $InvoiceTrackerTableEndPoint . '?page=' . $page . '&rows_per_page=' . $per_page . '&filters=' . urlencode(json_encode($filter_criteria)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Knack-Application-Id: ' . $app_id,
            'X-Knack-REST-API-Key: ' . $api_key,
            'Content-Type: application/json'
        ));

        // Execute the request
        $response = curl_exec($ch);

        // Check for errors
        if (curl_errno($ch)) {
            echo 'cURL error: ' . curl_error($ch);
            curl_close($ch);
            exit;
        }

        // Close cURL resource
        curl_close($ch);

        // Decode the JSON response
        $data = json_decode($response, true);

        // Check if decoding was successful
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo 'JSON error: ' . json_last_error_msg();
            exit;
        }

        // Check if records exist
        if (isset($data['records']) && is_array($data['records'])) {
            $all_records = array_merge($all_records, $data['records']);
            $page++;
        } else {
            break; // Exit loop if no more records
        }
    } while (isset($data['total_pages']) && $page <= $data['total_pages']);
    

     // Display the data
     echo '<h3 style="color:#800080;">>> Fetching Invoices To Be Created</h3>';
     echo '<table border="1" style="color:#ff0090 ;">';
     echo "<tr><th>Job  Number</th><th>Xero Invoice Tracker Name</th><th>Customer Number</th><th>Job Card</th><th>Ready To Create</th><th>Status</th></tr>";
     $knack_data_push_to_xero = array();
     
     foreach ($all_records as $record) {
        $final_line_items = array();

         $InvoiceTrackerName = $record['field_226'];
         $jobNumber = $record['field_230'];
         $customerNumber = $record['field_231'];
         $readyToCreate = $record ['field_232'];
         $jobCardNumber = $record['field_234'];
         
         echo '<tr>
                 <td>'.$jobNumber.'</td>
                 <td>'.$InvoiceTrackerName.'</td>
                 <td>'.$customerNumber.'</td>
                <td>'.$jobCardNumber.'</td>
                 <td>'.$readyToCreate.'</td>
                 <td id="'.$record['id'].'">Creating...</td>
                </tr>';

         //============== Fetch job from Knack
         echo '<br/><b style="color:#0f1d68;">### Started Invoicing Process For The Job:'.$jobNumber.'</b>';

         echo "<br/># Fetching the associated job.";
         $job = find_job_record($JobCardTableEndPoint, $api_key, $app_id, $jobNumber);
         $job = $job['records'][0];
         $job_id = $job['id'];
         $dispatch_field_value = $job['field_99']; // E20 Courier - $7.00

         // Step 1: Split the string at the ' - ' separator
         $parts = explode(' - ', $dispatch_field_value);
         
         // Step 2: Extract the dispatch method (first part)
         $dispatch_method = $parts[0]; // "E20 Courier"
         
         // Step 3: Extract the cost (second part) and remove the dollar sign
         $dispatchCost = floatval(str_replace('$', '', $parts[1])); // 7.00

        
        $final_line_items[] = [
            'Description' =>strip_tags( "Job: $jobNumber \nRego: " . strtoupper($job['field_18']) . " \nVIN: " . $job['field_17'] . " \nExemption: " . $job['field_26'] . " \nCustomer: " .  $job['field_25']),
            'Quantity' => 0,
            'UnitAmount' => 0,
            'AccountCode' => '200',  // Ensure this is a valid Xero account code
            'TaxType' => 'NONE',  // Tax type from the example, none means exempted
            'LineAmount' => 0  // LineAmount (calculated as Quantity * UnitAmount)
        ];
        

         $final_line_items[]=[

            'Description' => strip_tags($dispatch_method), //remove html like <span class="".........
            'Quantity' => 1,
            'UnitAmount' => $dispatchCost,
            'AccountCode' => '200',  // Ensure this is a valid Xero account code
            'TaxType' => 'NONE',  // Tax type from the example, none means exempted
            'LineAmount' => $dispatchCost  // LineAmount (calculated as Quantity * UnitAmount)
        ];

         //=============== Fetch product line items from knack
         echo '<br/># Fetching associated product line items.';
         //passing $job_id instead of job card number because job card number field is connection type and needs rec id to be passed instead.
         $prod_line_items = read_product_line_items($ProdLineItemsTableEndPoint, $job_id, $app_id, $api_key); 
          
         foreach($prod_line_items['records'] as $prod){

            $final_line_items[]=[
                'Description' => strip_tags($prod['field_85']), 
                'Quantity' => $prod['field_54'],
                'UnitAmount' => convertCurrency($prod['field_55']),
                'AccountCode' => '200',  // Ensure this is a valid Xero account code
                'TaxType' => 'NONE',  // Tax type from the example, none means exempted
                'LineAmount' => convertCurrency($prod['field_57']) ??  $prod['field_54'] * convertCurrency($prod['field_55'])  // LineAmount (calculated as Quantity * UnitAmount)
            ];

         }

        

         //=============== Fetch service line items
         echo '<br/># Fetching associated service line items.';
         $service_line_items = read_service_line_items($ServLineItemsTableEndPoint, $job_id, $app_id, $api_key);
         foreach($service_line_items['records'] as $serv){
           
            $final_line_items[]=[
                'Description' => strip_tags($serv['field_77']), 
                'Quantity' => $serv['field_79'],
                'UnitAmount' =>convertCurrency($serv['field_80']),
                'AccountCode' => '200',  // Ensure this is a valid Xero account code
                'TaxType' => 'NONE',  // Tax type from the example, none means exempted
                'LineAmount' =>convertCurrency($serv['field_84']) ?? $serv['field_79'] * convertCurrency($serv['field_80'])  // LineAmount (calculated as Quantity * UnitAmount)
            ];

         }
        
         //============== Fetch customer from Knack
         $customer = find_customer_record($customerNumber, $CustomersTableEndPoint, $app_id, $api_key);   
         $customer = $customer['records'][0];  
        //  echo "<pre>";
        //  print_r($customer);
        //  echo"</pre>";
        //  die;

         $knack_data_push_to_xero[] = [
            'invTrackerRecId'           => $record['id'],
             'invoiceTrackerName'       => $InvoiceTrackerName,
             'jobNumber'                => $jobNumber,
             'customerNumber'           => $customerNumber,
             'notes'                    => $job['field_33'],
             'customer'                 => $job['field_25'],
             'xeroAccountNumber'        => $customer['field_326'],
             'xeroContactId'            => $customer['field_382'],
             'exemptionNumber'          => $job['field_26'],
             'regoNumber'               => $job['field_18'],
             'vinNumber'                => $job['field_17'],
             'jobStatus'                => $job['field_97'],
             'dispatchMethod'           => $dispatch_method,
             'dispatchCost'             => $dispatchCost,
             'finalLineItems'           => $final_line_items,
            ];

            $xeroContactID = $customer['field_382'];
            

            //===================== Create Xero Invoice
            $dueDays = 30;
            $result = create_xero_invoice($xeroContactID, $tenantID, $accessToken, $final_line_items, $dueDays = 30);
            echo "<pre> The invoice number is:";
            print_r($result);
            echo"</pre>";
            $knack_data_push_to_xero[] = [
                'invoiceNumber'             => $result,
                'xeroInvCreationStart'      => date('Y-m-d'),
                'xeroInvCreationEnd'      => date('Y-m-d')
            ];
                        
            if ($result) {
                echo '<script>document.querySelector("#'.$record['id'].'").innerHTML = "'.$result.'"</script>';
                 $message = "Invoice created successfully! Invoice Number: " . $result. " Updatig Invoice tracker table.";
                 logMessage($message);
                 update_xero_invoice_tracker($InvoiceTrackerTableEndPoint, $knack_data_push_to_xero, $app_id, $api_key);

                echo $message;
            } else {
                echo '<script>document.querySelector("#'.$record['id'].'").innerHTML = "Error creting Invoice in Xero"</script>';

            }

            //Create an audit log in table: System Audit Log
            update_system_audit_log ($KnackSysAuditLogEndPoint, $knack_data_push_to_xero, $app_id, $api_key);

            break; // exit after one iteration
            echo "<br/><br/><br/>";
     }
 
     echo "</table>";
     echo "<br/><br/><br/>";
}


function find_job_record($JobCardTableEndPoint, $api_key, $app_id, $jobNumber)
{ // Filter criteria 
    $filter_criteria = [
       // 'match' => 'or',
        'rules' => [
            [
                'field' => 'field_90',  // job number
                'operator' => 'is',
                'value' =>  $jobNumber,
            ],
            
        ],
    ];

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    $url = $JobCardTableEndPoint . '?filters=' . urlencode(json_encode($filter_criteria));
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
            $message = "<br/ >> Job record found against this job number: <b>$jobNumber</b>";
            logMessage($message);
            echo $message;
          //  echo ("<br/>Job record found and it is printed above<br/><br/>");
        } else {
            echo "Request failed with HTTP status code: $http_code";
        }
    }

    // Close cURL
    curl_close($ch);

    return $response_data;
 
 }

 function find_customer_record($customerNumber, $CustomersTableEndPoint, $app_id, $api_key) {
    // Filter criteria 
    $filter_criteria = [
        'match' => 'and',
        'rules' => [
            [
                'field' => 'field_10',  // customer number
                'operator' => 'is',
                'value' =>  $customerNumber,
            ],
        ],
    ];

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    $url = $CustomersTableEndPoint . '?filters=' . urlencode(json_encode($filter_criteria));
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
            $message = "<br/>Customer record found.";
            logMessage($message);
            echo ("<br/>> Customer record found.");
            
        } else {
            echo "Request failed with HTTP status code: $http_code";
        }
    }

    // Close cURL
    curl_close($ch);

    return $response_data;
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
               'field' => 'field_58',  // job card number
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

function convertCurrency($amount) {
    // Remove any non-numeric characters except the decimal point
    $cleanAmount = preg_replace('/[^\d.]/', '', $amount);
    return number_format((float)$cleanAmount, 2, '.', '');
}

function create_xero_invoice($xeroContactID, $tenantID, $accessToken, $final_line_items, $dueDays = 30) {

    $xeroContactID = '8a8fb8ad-e6ff-4c3e-b871-515124e40840';

    // Prepare the invoice data
    // $xfinal_line_items = [
    //     [
    //         'Description' => 'Transcend 1TB SSD',  // Description from the example
    //         'Quantity' => 2,
    //         'UnitAmount' => 30.00,
    //         'AccountCode' => '200',  // Ensure this is a valid Xero account code
    //         'TaxType' => 'NONE',  // Tax type from the example
    //         'LineAmount' => 60.00  // LineAmount (calculated as Quantity * UnitAmount)
    //     ],
    //     [
    //         'Description' => '2CC Brown Acme Tires',  // Description from the example
    //         'Quantity' => 2,
    //         'UnitAmount' => 20.00,
    //         'AccountCode' => '200',  // Ensure this is a valid Xero account code
    //         'TaxType' => 'NONE',  // Tax type from the example
    //         'LineAmount' => 40.00  // LineAmount (calculated as Quantity * UnitAmount)
    //     ]
    //     ];
            echo '<pre> final line items are';
            print_r($final_line_items);
            echo '</pre>';
    $invoice = [
        'Invoices' => [
            [
                'Type' => 'ACCREC',  // 'ACCREC' for Accounts Receivable
                'Contact' => [
                    'ContactID' =>$xeroContactID
                ],
                'LineItems' =>$final_line_items ,  // Ensure final_line_items is correctly formatted
                'Date' => (new DateTime())->format('Y-m-d'),  
                'DueDate' => '2018-12-10',  
                'Reference' => 'App Design',  // job number | short vin number
                'Status' => 'DRAFT',  // AUTHORISED, PAID, VOID, CANCELLED, SUBMITTED
                // "CurrencyCode" => "NZD" curently allows USD
                //"BrandingThemeID" => "38523938-df80-40e8-b42c-cc2bae4e961d",

            ]
        ]
    ];



    // Create a Guzzle client to send the request
    $client = new Client([
        'base_uri' => 'https://api.xero.com/api.xro/2.0/',
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
            'Xero-Tenant-Id' => $tenantID,
        ],
    ]);

    try {
       // Send the POST request to Xero API to create the invoice
        $response = $client->post('Invoices', [
            'json' => $invoice,  // Send the invoice data as JSON
        ]);
       // $response = $client->get('Invoices');

        // Decode the response
        $data = json_decode($response->getBody(), true);

        // Debug: Print the response for analysis
        echo '<pre>';
        print_r($data);
        echo '</pre>';

        // Check if the invoice was created successfully
        if (isset($data['Invoices']) && count($data['Invoices']) > 0) {
            echo "Invoice created successfully in Xero. Invoice Number: " . $data['Invoices'][0]['InvoiceNumber'];
            return $data['Invoices'][0]['InvoiceNumber'];
        } else {
            echo "Error creating invoice: " . json_encode($data);
        }
    } catch (\Exception $e) {
        // Catch and display any errors from Guzzle request
        echo "Request error: ";
        echo "<pre>";
        print_r( $e->getMessage());
        echo "</pre>";
    }
}
    

function update_xero_invoice_tracker($InvoiceTrackerTableEndPoint, $data, $app_id, $api_key){
    // echo "<pre>";
    // print_r( $data);
    // echo "</pre>";

    // Define the record ID and the fields to update
    $knackRecordID = $data[0]['invTrackerRecId'];
    $xeroLastUpdated = date('Y-m-d H:i:s');

    // Define the URL for the API request. $CustomersTableEndPoint is "https://api.knack.com/v1/objects/object_1/records"
    $url = $InvoiceTrackerTableEndPoint . '/' . $knackRecordID;

    // Define the data to be sent
    $data = [
      //  'field_226' => $data[0]['invoiceNumber'], //Xero invoice tracker name
        'field_235' => $data[1]['invoiceNumber']." / Created Draft Invoice", // Status note
        'field_228' => $xeroLastUpdated,
        'field_232' => null, //Empty the Ready to create field
        'field_234' => null //Empty the Job card field
    ];

    // Initialize cURL
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-Knack-Application-Id: $app_id",
        "X-Knack-REST-API-Key: $api_key",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        // Log the successful fetch of customer data
        logMessage("Customer record successfully updated in Knack (Invoice Tracker Table) table. Record ID: $knackRecordID");
        echo ("<br/>Customer record successfully updated in Knack (Invoice Tracker Table) table. Record ID: $knackRecordID <br/><br/>");
    }

    // Close cURL
    curl_close($ch);

}

function update_system_audit_log ($KnackSysAuditLogEndPoint, $data, $app_id, $api_key){

   
    echo "<pre>";
    print_r( $data);
    echo "</pre>";

    // Define the record ID and the fields to update
    $knackRecordID = $data[0]['invTrackerRecId'];
    $xeroLastUpdated = date('Y-m-d H:i:s');

    // Define the URL for the API request. $CustomersTableEndPoint is "https://api.knack.com/v1/objects/object_1/records"
    $url = $KnackSysAuditLogEndPoint;

    // Define the data to be sent
    $data = [
        "field_174" => "Job Dashboard> Xero Invoice",
        "field_176" => date("Y-m-d H:i:s"),
        "field_177" => $data[0]['jobNumber'],
        "field_178" => "Invoice creation fro Xero invoice tracker. Initiated by Admin in App Dashboard.",
        "field_179" => $data[1]['invoiceNumber'],
        "field_180" => $data[0]['customerNumber'],
        "field_182" => "Invoice Tracker Rec Modified:".$knackRecordID,
        "field_183" => "Xero creation start / last updated field(s) modified:".$xeroLastUpdated,
        "field_184" => "Xero invoice number:".$data[1]['invoiceNumber']."|For customer:".$data[0]['customerNumber']."|Created/Updated on:".$xeroLastUpdated."|Job Number:".$data[0]['jobNumber']
    ];

    // Initialize cURL
    $ch = curl_init($url);

    // Set cURL options
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "X-Knack-Application-Id: $app_id",
        "X-Knack-REST-API-Key: $api_key",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    } else {
        // Log the successful fetch of customer data
        logMessage("Invoice Record successfully created in Knack (System Audit Log) table.");
        echo ("<br/>Invoice Record successfully created in Knack (System Audit Log) table. <br/><br/>");
    }

    // Close cURL
    curl_close($ch);


}