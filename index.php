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
            // Failed to get the access token or user details.
            exit($e->getMessage());
        }

        echo "</div>";
    }


//================== Let's fetch customers from Knack and filter on the basis of different fields
$knack_customers_data = fetch_customers_from_knack($CustomersTableEndPoint, $api_key, $app_id);
echo "<pre>";
print_r ($knack_customers_data);
echo "</pre>";

//=================== Create Customer in Xero
create_or_update_customer_in_xero($knack_customers_data, $tenantID, $provider, $accessToken);
// try{
// create_customer_in_xero($knack_customers_data, $tenantID, $provider, $accessToken);
// }
// catch (IdentityProviderException $e) {
//     // Handle exceptions
//     exit('Error: ' . $e->getMessage());
// }



//=========================>>>>>>>>>>> DEFINE FUNCTIONALITY <<<<<<<<<<<<============================================
//==================================================================================================================
//================================================================================================================//
function fetch_customers_from_knack($CustomersTableEndPoint, $api_key, $app_id){
    // Filter criteria to check if field_225 is not blank
    $filter_criteria = [
        'match' => 'and',
        'rules' => [
            [
                'field' => 'field_225',
                'operator' => 'is blank'
            ],
        ],
    ];

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $CustomersTableEndPoint . '?filters=' . urlencode(json_encode($filter_criteria)));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'X-Knack-Application-Id: ' . $app_id,
        'X-Knack-REST-API-Key: ' . $api_key,
        'Content-Type: application/json'

    ));

    // Execute the request - 
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
        
        echo '<h3 style="color:#800080;">>> Fetching Customers Details</h3>';
        
        // Display the data
        echo '<table border="1" style="color:#ff0090 ;">';
        echo "<tr><th>Customer Number</th><th>Company Name</th><th>Contact Name</th><th>Xero Account Number</th><th>Xero Last Updated</th><th>Xero Cust. Number</th></tr>";
        $knack_customers = array();
        foreach ($data['records'] as $record) {
            $customerNumber = $record['field_10'] ?? 'N/A';
            $companyName = $record['field_1'] ?? 'N/A';
            $contactName = $record['field_93'] ?? 'N/A';
            $xeroAccountNumber = $record['field_4'] ?? 'N/A';
            $xeroLastUpdated = $record['field_225'] ?? 'N/A';
            $xeroCustomerNumber = $record['field_10'] ? 'CUST-'.$record['field_10'] : 'N/A';


            echo "<tr>
                    <td>$customerNumber</td>
                    <td>$companyName</td>
                    <td>$contactName</td>
                    <td>$xeroAccountNumber</td>
                    <td>$xeroLastUpdated</td>
                    <td>$xeroCustomerNumber</td>


                </tr>";

            $knack_customers[] = [$customerNumber, $companyName, $contactName, $xeroAccountNumber, $xeroCustomerNumber];
        }

        echo "</table>";
        echo "<br/><br/><br/>";
    } else {
        echo 'No records found or invalid data structure.';
    }

    echo '<br/><div class="raw-contacts-info">';
    print_r($data['records']);
    echo '</div>';
    echo '<br/>';
    
    return $knack_customers;
}

//================================= Create a POST request for Customer to Xero
//==================================================================================//
function create_or_update_customer_in_xero($knack_customers_data, $tenantID, $provider, $accessToken) {
    // Prepare the customer data from Knack
    foreach ($knack_customers_data as $customer) {
        $xeroCustomerNumber = $customer[4]; // Assuming the customer number is at index 4
        
        // Check if the customer already exists in Xero
        $existingCustomerId = search_customer_in_xero($xeroCustomerNumber, $tenantID, $provider, $accessToken);
        
        if ($existingCustomerId) {
            // If customer exists, update it
            update_customer_in_xero($existingCustomerId, $customer, $tenantID, $provider, $accessToken);
        } else {
            // If customer does not exist, create a new one
            create_customer_in_xero_entry($customer, $tenantID, $provider, $accessToken);
        }
    }
}

function search_customer_in_xero($xeroCustomerNumber, $tenantID, $provider, $accessToken) {
    $options = [
        'headers' => [
            'xero-tenant-id' => $tenantID,
            'Accept' => 'application/json'
        ]
    ];
    
    $searchUrl = 'https://api.xero.com/api.xro/2.0/Contacts';
    $searchResponse = $provider->getAuthenticatedRequest('GET', $searchUrl, $accessToken, $options);
    $searchData = $provider->getParsedResponse($searchResponse);

    foreach ($searchData['Contacts'] as $contact) {
        if (isset($contact['ContactNumber']) && $contact['ContactNumber'] === $xeroCustomerNumber) {
            return $contact['ContactID']; // Return the Contact ID if matched
        }
    }
    
    return null; // Return null if no match is found
}

function update_customer_in_xero($contactId, $customer, $tenantID, $provider, $accessToken) {
    $customerData = [
        'Contacts' => [
            [
                'ContactID' => $contactId,
                'Name' => $customer[1], // Company Name
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
        echo '<h3 style="color:#8bbe1b;">Customer updated successfully in Xero</h3>';
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        exit('Error updating customer: ' . $e->getMessage());
    }
}

function create_customer_in_xero_entry($customer, $tenantID, $provider, $accessToken) {
    $customerData = [
        'Contacts' => [
            [
                'Name' => $customer[1], // Company Name
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
        if ($response['Status'] == 'OK') {
            echo '<h3 style="color:#8bbe1b;">Customer created successfully in Xero</h3>';
        } else {
            echo '<pre>' . print_r($response, true) . '</pre>';
        }
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        exit('Error creating customer: ' . $e->getMessage());
    }
}

?>

<html>
<head>
	<title>Create Customers in Xero - Seatbelts4u</title>
    <style>
        textarea { border:1px solid #999999;  width:75%; height: 75%;  margin:5px 0; padding:3px;  }
        body{width:60%; overflow:scroll;}
    </style>
</head>
<body>

<div class="raw-contacts-info-con"><h3 style="color:#8b008b;">>> Raw Data for identification</h3></div>

<script src="jquery-3.7.1.min.js"></script>
<script type="text/javascript">
jQuery(document).ready(function($){

    $('.raw_connection_info').slideUp();
    $('.success').click(function(){

    $('.raw_connection_info').slideToggle('slow');

    });

    //place raw contacts info at bottom
    $('.raw-contacts-info-con').append($('.raw-contacts-info'));
});
</script>

</body>
</html>