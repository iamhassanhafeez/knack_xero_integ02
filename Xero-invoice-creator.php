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

$api_key = '5731568a-75ed-4a6e-b906-7c3cda415405';
$app_id = '64ec0e7df4070c0028ff4a07';

/*
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
        logMessage("Access token retrieved successfully.");

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
        logMessage("Xero tenant ID retrieved: $tenantID");

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
        logMessage("Error retrieving access token or user details: " . $e->getMessage());

        // Failed to get the access token or user details.
        exit($e->getMessage());
    }

    echo "</div>";
}
*/
$jobNumber = 102695;
find_job_record($JobCardTableEndPoint, $api_key, $app_id, $jobNumber);
//=========================>>>>>>>>>>> DEFINE FUNCTIONALITY <<<<<<<<<<<<============================================
//==================================================================================================================

// Function to log messages to a file
function logMessage($message)
{
    $logFile = 'app-logs.log'; // Path to your log file
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

function xero_invoice_tracker_in_knack($InvoiceTrackerTableEndPoint, $CustomersTableEndPoint, $JobCardTableEndPoint, $ProdLineItemsTableEndPoint, $ServLineItemsTableEndPoint, $api_key, $app_id, $accessToken){
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
     echo "<tr><th>Job  Number</th><th>Xero Invoice Tracker Name</th><th>Customer Number</th><th>Job Card</th><th>Ready To Create</th></tr>";
     $knack_data_push_to_xero = array();
     
     foreach ($all_records as $record) {
         $InvoiceTrackerName = $record['field_226'];
         $jobNumber = $record['field_230'];
         $customerNumber = $record['field_231'];
         $readyToCreate = $record ['field_232'];
         $jobCardNumber = $record['field_234'];

         echo "<tr>
                 <td>$jobNumber</td>
                 <td>$InvoiceTrackerName</td>
                 <td>$customerNumber</td>
                <td>$jobCardNumber</td>
                 <td>$readyToCreate</td>
                </tr>";

         //============== Fetch job from Knack
         $job = find_job_record($JobCardTableEndPoint, $api_key, $app_id, $jobNumber);
         $dispatch_field_value = $job['field_99']; // E20 Courier - $7.00

         // Step 1: Split the string at the ' - ' separator
         $parts = explode(' - ', $dispatch_field_value);
         
         // Step 2: Extract the dispatch method (first part)
         $dispatch_method = $parts[0]; // "E20 Courier"
         
         // Step 3: Extract the cost (second part) and remove the dollar sign
         $dispatchCost = floatval(str_replace('$', '', $parts[1])); // 7.00

         //=============== Fetch product line items from knack
         $prod_line_items = read_product_line_items($ProdLineItemsTableEndPoint, $jobCardNumber, $app_id, $api_key);

         //=============== Fetch service line items
         $service_line_items = read_service_line_items($ServLineItemsTableEndPoint, $jobCardNumber, $app_id, $api_key);

         //============== Fetch customer from Knack
         $customer = find_customer_record($customerNumber, $CustomersTableEndPoint, $app_id, $api_key);     

         $knack_data_push_to_xero[] = [
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
             'prodLineitems'            => $prod_line_items,
             'servLineiTems'            => $service_line_items
            ];

            //===================== Create Xero Invoice
            $result = create_xero_invoice($knack_data_push_to_xero, $accessToken, $dueDays);
                        
            if ($result['success']) {
                echo "Invoice created successfully! Invoice ID: " . $result['invoiceId'];
            } else {
                echo "Error: " . $result['error'];
            }
     }
 
     echo "</table>";
     echo "<br/><br/><br/>";
 
     echo '<br/><div class="raw-job info-info">';
     echo '<pre>';
     print_r($all_records);
     echo '</pre>';
     echo '</div>';
     echo '<br/>';
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

            echo '<pre>';
            print_r($response_data);
            echo '</pre>';
            echo ("<br/>Job record found and it is printed above<br/><br/>");
        } else {
            echo "Request failed with HTTP status code: $http_code";
        }
    }

    // Close cURL
    curl_close($ch);

    return $response;
 
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

            echo '<pre>';
            print_r($response_data);
            echo '</pre>';
            echo ("<br/>Customer record found and it is printed above<br/><br/>");
        } else {
            echo "Request failed with HTTP status code: $http_code";
        }
    }

    // Close cURL
    curl_close($ch);

    return $response;
}

function read_product_line_items($ProdLineItemsTableEndPoint, $jobCardNumber, $app_id, $api_key){
     // Filter criteria 
     $filter_criteria = [
        'match' => 'and',
        'rules' => [
            [
                'field' => 'field_58',  // customer number
                'operator' => 'is',
                'value' =>  $jobCardNumber,
            ],
        ],
    ];

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    $url = $ProdLineItemsTableEndPoint . '?filters=' . urlencode(json_encode($filter_criteria));
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

            echo '<pre>';
            print_r($response_data);
            echo '</pre>';
            echo ("<br/>Product line items found and it is printed above<br/><br/>");
        } else {
            echo "Request failed with HTTP status code: $http_code";
        }
    }

    // Close cURL
    curl_close($ch);

    return $response;
    
}

function read_service_line_items($ServiceLineItemsTableEndPoint, $jobCardNumber, $app_id, $api_key){
    // Filter criteria 
    $filter_criteria = [
       'match' => 'and',
       'rules' => [
           [
               'field' => 'field_58',  // customer number
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

           echo '<pre>';
           print_r($response_data);
           echo '</pre>';
           echo ("<br/>Service line items found and it is printed above<br/><br/>");
       } else {
           echo "Request failed with HTTP status code: $http_code";
       }
   }

   // Close cURL
   curl_close($ch);

   return $response;
   
}

function create_xero_invoice($data_to_push, $accessToken, $dueDays = 30) {
    // Set up the HTTP client (Guzzle)
    $client = new Client([
        'base_uri' => 'https://api.xero.com/api.xro/2.0/',
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
        ],
    ]);
    
    // Create the invoice data
    $invoice = [
        'Type' => 'ACCREC',  // 'ACCREC' for Accounts Receivable or 'ACCPAY' for Accounts Payable
        'Contact' => [
            'ContactID' => $contactId,  // Contact ID (you must have this beforehand)
        ],
        'LineItems' => $lineItems,  // Line items (array of items on the invoice)
        'Date' => date('Y-m-d'),  // Today's date
        'DueDate' => date('Y-m-d', strtotime("+$dueDays days")),  // Due date (default 30 days from now)
      //  'Reference' => $reference,  // Invoice reference (optional)
    ];
    
    try {
        // Make the POST request to create the invoice
        $response = $client->post('Invoices', [
            'json' => [$invoice],  // Pass the invoice data as JSON
        ]);

        // Decode the response body
        $body = $response->getBody();
        $data = json_decode($body, true);
        
        // Check if the invoice creation was successful
        if (isset($data['Invoices']) && count($data['Invoices']) > 0) {
            return [
                'success' => true,
                'invoiceId' => $data['Invoices'][0]['InvoiceID'],  // Return the created Invoice ID
                'invoice' => $data['Invoices'][0],  // Return the full invoice data
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Error creating invoice: ' . json_encode($data),
            ];
        }
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        // Handle errors such as network or API errors
        return [
            'success' => false,
            'error' => 'Request error: ' . $e->getMessage(),
        ];
    }
}