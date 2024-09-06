<?php
require __DIR__ . '/vendor/autoload.php';

session_start();

//============ Xero Config ==================
$clientId = 'DB8962151A3D4C339A0D4B1E12712771';
$clientSecret = 'hnXTTDTWyKi4Crhw2TEPnxmyP2qX92TH-HoCadvanaVX9w-P';
// old $redirectUri = 'http://localhost/xero-app-new/index.php';
$redirectUri = 'http://localhost/knack_xero_integration/index.php';

$provider = new \League\OAuth2\Client\Provider\GenericProvider([
    'clientId'                => $clientId,
    'clientSecret'            => $clientSecret,
    'redirectUri'             => $redirectUri,
    'urlAuthorize'            => 'https://login.xero.com/identity/connect/authorize',
    'urlAccessToken'          => 'https://identity.xero.com/connect/token',
    'urlResourceOwnerDetails' => 'https://api.xero.com/api.xro/2.0/Invoices'
]);


// =============================== Knack Configuration =======================
$CustomersTableEndPoint = 'https://api.knack.com/v1/objects/object_1/records';
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
        echo '<div class="raw_connection_info" style="color:#c40233;">';
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


//================== Let's fetch customers from Knack and filter on the basis of different fields
$knack_customers_data = fetch_customers_from_knack($CustomersTableEndPoint, $api_key, $app_id);
echo "<pre>";
print_r($knack_customers_data);
echo "</pre>";

//=================== Create Customer in Xero
create_or_update_customer_in_xero($knack_customers_data, $tenantID, $provider, $accessToken, $CustomersTableEndPoint, $api_key, $app_id);




//=========================>>>>>>>>>>> DEFINE FUNCTIONALITY <<<<<<<<<<<<============================================
//==================================================================================================================

// Function to log messages to a file
function logMessage($message)
{
    $logFile = 'app-logs.log'; // Path to your log file
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}


//============================================== Fetch Customers from Knack DB =========================================//
function fetch_customers_from_knack($CustomersTableEndPoint, $api_key, $app_id)
{

    $all_records = [];
    $page = 1;
    $per_page = 100; // You can adjust this if needed (max 100 per page)

    // Filter criteria 
    $filter_criteria = [
        'match' => 'and',
        'rules' => [
            [
                'field' => 'field_225',
                'operator' => 'is blank'
            ],
            [
                'field' => 'field_186',
                'operator' => 'is not',
                'value' => 'no'
            ],
            [
                'field' => 'field_336',
                'operator' => 'is',
                'value' => 'no'
            ],
        ],
    ];

    do {
        // Initialize cURL
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $CustomersTableEndPoint . '?page=' . $page . '&rows_per_page=' . $per_page . '&filters=' . urlencode(json_encode($filter_criteria)));
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
            logMessage("Successfully fetched customers fron Knack DB for page: $page");

            $page++;
        } else {
            break; // Exit loop if no more records
        }
    } while (isset($data['total_pages']) && $page <= $data['total_pages']);

    // Display the data
    echo '<h3 style="color:#800080;">>> Fetching Customers Details</h3>';

    echo '<table border="1" style="color:#ff0090 ;">';
    echo "<tr><th>Customer Number</th><th>Company Name</th><th>Contact First</th><th>Contact Last</th><th>Xero Account Number</th><th>Xero Last Updated</th><th>Xero Cust. Number</th></tr>";
    $knack_customers = array();
    foreach ($all_records as $record) {
        $knackRecordID = $record['id'] ?? 'N/A';
        $billingFirstName = $record['field_65_raw']['first'] ?? 'N/A';
        $billingLastName = $record['field_65_raw']['last'] ?? 'N/A';
        $billingPhone = $record['field_66'] ?? 'N/A';
        $billingEmail = $record['field_61'] ?? 'N/A';
        $customerNumber = $record['field_10'] ?? 'N/A';
        $companyName = $record['field_1'] ?? 'N/A';
        $contactFirstName = $record['field_93_raw']['first'] ?? 'N/A';
        $contactLastName = $record['field_93_raw']['last'] ?? 'N/A';
        $address = $record['field_4'] ?? 'N/A';
        $address2 = $record['field_5'] ?? 'N/A';
        $suburb = $record['field_6'] ?? 'N/A';
        $city = $record['field_7'] ?? 'N/A';
        $postCode = $record['field_8'] ?? 'N/A';
        $xeroAccountNumber = $record['field_4'] ?? 'N/A';
        $xeroLastUpdated = $record['field_225'] ?? 'N/A';
        $xeroCustomerNumber = $record['field_10'] ? 'CUST-' . $record['field_10'] : 'N/A';

        echo "<tr>
                <td>$customerNumber</td>
                <td>$companyName</td>
                <td>$contactFirstName</td>
                <td>$contactLastName</td>
                <td>$xeroAccountNumber</td>
                <td>$xeroLastUpdated</td>
                <td>$xeroCustomerNumber</td>
            </tr>";

        $knack_customers[] = [
            'knackRecordID'         => $knackRecordID,
            'billingFirstName'      =>  $billingFirstName,
            'billingLastName'       => $billingLastName,
            'billingPhone'          => $billingPhone,
            'billingEmail'          => $billingEmail,
            'customerNumber'        => $customerNumber,
            'companyName'           => $companyName,
            'contactFirstName'      => $contactFirstName,
            'contactLastname'       => $contactLastName,
            'address'               => $address,
            'address2'              => $address2,
            'suburb'                => $suburb,
            'city'                  => $city,
            'postCode'              => $postCode,
            'xeroCustomerNumber'    => $xeroCustomerNumber
        ];
    }

    echo "</table>";
    echo "<br/><br/><br/>";

    echo '<br/><div class="raw-contacts-info">';
    echo '<pre>';
    print_r($all_records);
    echo '</pre>';
    echo '</div>';
    echo '<br/>';

    return $knack_customers;
}

function update_knack_record($xeroAccountNumber, $customer, $CustomersTableEndPoint, $api_key, $app_id)
{

    // Define the record ID and the fields to update
    $knackRecordID = $customer['knackRecordID'];
    $xeroLastUpdated = date('Y-m-d H:i:s');

    // Define the URL for the API request. $CustomersTableEndPoint is "https://api.knack.com/v1/objects/object_1/records"
    $url = $CustomersTableEndPoint . '/' . $knackRecordID;

    // Define the data to be sent
    $data = [
        'field_225' => $xeroLastUpdated,
        'field_326' => $xeroAccountNumber
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
        logMessage("Customer record successfully updated in Knack (Customers) table. Record ID: $knackRecordID");
        echo ("Customer record successfully updated in Knack (Customers) table. Record ID: $knackRecordID");
    }

    // Close cURL
    curl_close($ch);
}

//================================= Create a POST request for Customer to Xero
//==================================================================================//
function create_or_update_customer_in_xero($knack_customers_data, $tenantID, $provider, $accessToken, $CustomersTableEndPoint, $api_key, $app_id)
{
    // Prepare the customer data from Knack
    foreach ($knack_customers_data as $customer) {
        $xeroCustomerNumber = $customer['xeroCustomerNumber']; // Assuming the customer number is at index 4
        // Check if the customer already exists in Xero
        $existingCustomerId = search_customer_in_xero($xeroCustomerNumber, $tenantID, $provider, $accessToken);
        echo $existingCustomerId;
        if ($existingCustomerId) {
            // If customer exists, update it
            update_customer_in_xero($existingCustomerId, $customer, $tenantID, $provider, $accessToken, $CustomersTableEndPoint, $api_key, $app_id);
        } else {
            // If customer does not exist, create a new one
            create_customer_in_xero_entry($customer, $tenantID, $provider, $accessToken, $CustomersTableEndPoint, $api_key, $app_id);
        }
    }
}

function search_customer_in_xero($xeroCustomerNumber, $tenantID, $provider, $accessToken)
{
    $options = [
        'headers' => [
            'xero-tenant-id' => $tenantID,
            'Accept' => 'application/json'
        ]
    ];
    $searchUrl = 'https://api.xero.com/api.xro/2.0/Contacts?where=AccountNumber=' . '"' . $xeroCustomerNumber . '"';
    echo $searchUrl;

    try {
        $searchResponse = $provider->getAuthenticatedRequest('GET', $searchUrl, $accessToken, $options);
        $searchData = $provider->getParsedResponse($searchResponse);

        echo "<pre>";
        print_r($searchData);
        echo "</pre>";

        if (!empty($searchData['Contacts'])) {
            foreach ($searchData['Contacts'] as $contact) {
                if (isset($contact['AccountNumber']) && $contact['AccountNumber'] === $xeroCustomerNumber) {
                    return $contact['ContactID']; // Return the Contact ID if matched
                }
            }
        }
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        logMessage("Error searching customer in Xero: " . $e->getMessage());
        exit('Error searching customer in Xero: ' . $e->getMessage());
    }

    return null; // Return null if no match is found
}

function update_customer_in_xero($contactId, $customer, $tenantID, $provider, $accessToken, $CustomersTableEndPoint, $api_key, $app_id)
{
    $customerData = [
        'Contacts' => [
            [
                'ContactID' => $contactId,
                'Name' => $customer['companyName'], // Company Name
                'EmailAddress' => 'example@example.com', // Update with actual email
                'Phones' => [
                    [
                        'PhoneType' => 'MOBILE',
                        'PhoneNumber' => '1234567890' // Update with actual phone number
                    ]
                ],
                'Addresses' => [
                    [
                        'AddressType' => 'STREET',
                        'AddressLine1' => '123 Elm Street',
                        'City' => 'Springfield',
                        'Region' => 'IL',
                        'PostalCode' => '62701',
                        'Country' => 'USA'
                    ]
                ]
            ]
        ]
    ];

    $options = [
        'headers' => [
            'xero-tenant-id' => $tenantID,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode($customerData)
    ];

    $updateUrl = 'https://api.xero.com/api.xro/2.0/Contacts';
    try {
        $request = $provider->getAuthenticatedRequest('PUT', $updateUrl, $accessToken, $options);
        $response = $provider->getParsedResponse($request);
        if (isset($response['Status']) && $response['Status'] === 'OK') {
            logMessage("Customer updated successfully in Xero. ContactID: $contactId");
            echo '<h3 style="color:#8bbe1b;">Customer updated successfully in Xero</h3>';

            //Update record back in Knack
            $xeroAccountNumber = $response['Contacts'][0]['ContactID'];
            update_knack_record($xeroAccountNumber, $customer, $CustomersTableEndPoint, $api_key, $app_id);
        } else {
            echo '<pre>' . print_r($response, true) . '</pre>';
        }
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        logMessage("Error updating customer in Xero: " . $e->getMessage());
        exit('Error updating customer: ' . $e->getMessage());
    }
}

function create_customer_in_xero_entry($customer, $tenantID, $provider, $accessToken, $CustomersTableEndPoint, $api_key, $app_id)
{
    $customerData = [
        'Contacts' => [
            [
                'Name' => $customer['companyName'],
                'EmailAddress' => 'example@example.com', // Set default or actual email
                'Phones' => [
                    [
                        'PhoneType' => 'MOBILE',
                        'PhoneNumber' => '1234567890' // Set default or actual phone number
                    ]
                ],
                'Addresses' => [
                    [
                        'AddressType' => 'STREET',
                        'AddressLine1' => '123 Elm Street',
                        'City' => 'Springfield',
                        'Region' => 'IL',
                        'PostalCode' => '62701',
                        'Country' => 'USA'
                    ]
                ]
            ]
        ]
    ];

    $options = [
        'headers' => [
            'xero-tenant-id' => $tenantID,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ],
        'body' => json_encode($customerData)
    ];

    $createUrl = 'https://api.xero.com/api.xro/2.0/Contacts';
    try {
        $request = $provider->getAuthenticatedRequest('POST', $createUrl, $accessToken, $options);
        $response = $provider->getParsedResponse($request);
        if (isset($response['Status']) && $response['Status'] == 'OK') {
            $contactName = $response['Contacts'][0]['Name'];
            logMessage("Customer created successfully in Xero. Contact Name: $contactName");
            echo "<h3 style='color:#8bbe1b;'>Customer created successfully in Xero</h3> Contact Name: $contactName";

            //Update record back in Knack
            $xeroAccountNumber = $response['Contacts'][0]['ContactID'];
            update_knack_record($xeroAccountNumber, $customer, $CustomersTableEndPoint, $api_key, $app_id);
        } else {
            echo 'Customer status not found in the response or error creating customer inXero. <br/><pre>' . print_r($response, true) . '</pre>';
        }
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        logMessage("Error creating customer in Xero: " . $e->getMessage());
        exit('Error creating customer: ' . $e->getMessage());
    }
}

?> <html>

<head>
    <title>Create Customers in Xero - Seatbelts4u</title>
    <style>
    textarea {
        border: 1px solid #999999;
        width: 75%;
        height: 75%;
        margin: 5px 0;
        padding: 3px;
    }

    body {
        width: 60%;
        overflow: scroll;
    }
    </style>
</head>

<body>
    <div class="raw-contacts-info-con">
        <h3 style="color:#8b008b;">>> Raw Data for identification</h3>
    </div>
    <script src="jquery-3.7.1.min.js"></script>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.raw_connection_info').slideUp();
        $('.success').click(function() {
            $('.raw_connection_info').slideToggle('slow');
        });
        //place raw contacts info at bottom
        $('.raw-contacts-info-con').append($('.raw-contacts-info'));
    });
    </script>
</body>

</html>