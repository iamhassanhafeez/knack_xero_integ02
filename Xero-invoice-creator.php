<?php
require __DIR__ . '/vendor/autoload.php';

session_start();

//============ Xero Config ==================
$clientId = 'DB8962151A3D4C339A0D4B1E12712771';
$clientSecret = 'hnXTTDTWyKi4Crhw2TEPnxmyP2qX92TH-HoCadvanaVX9w-P';
// old $redirectUri = 'http://localhost/xero-app-new/index.php';
$redirectUri = 'http://localhost/knack_xero_integration/Xero-create-update-customers.php';

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

//=========================>>>>>>>>>>> DEFINE FUNCTIONALITY <<<<<<<<<<<<============================================
//==================================================================================================================

// Function to log messages to a file
function logMessage($message)
{
    $logFile = 'app-logs.log'; // Path to your log file
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}
