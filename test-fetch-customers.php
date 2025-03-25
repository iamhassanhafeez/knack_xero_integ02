<?php

// =============================== Knack Configuration =======================
$CustomersTableEndPoint = 'https://api.knack.com/v1/objects/object_1/records';
$api_key = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
$app_id = 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';



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



//================== Let's fetch customers from Knack and filter on the basis of different fields
$knack_customers_data = fetch_customers_from_knack($CustomersTableEndPoint, $api_key, $app_id);
echo "<pre>";
print_r($knack_customers_data);
echo "</pre>";





//============================== Backup function
function create_xero_invoice($xeroContactID,$tenantID, $final_line_items, $accessToken, $dueDays = 30) {
    // Set up the HTTP client (Guzzle)
    $client = new Client([
        'base_uri' => 'https://api.xero.com/api.xro/2.0/',
        'headers' => [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type'  => 'application/json',
            'Xero-Tenant-Id' => $tenantID,
        ],
    ]);    

    // Loop through the line items to clean up the UnitAmount values
    // foreach ($final_line_items as &$item) {
    //     // Remove dollar signs and commas, then cast to float
    //     $item['UnitAmount'] = (float)str_replace([ '$', ',' ], '', $item['UnitAmount']);
    // }
    $final_line_items = [
        [
            'Description' => 'Item Description',
            'Quantity' => 1,
            'UnitAmount' => 100.00,
            'AccountCode' => '200',  // Xero account code for this item
            // 'TaxType' => 'OUTPUT',   // Tax type like 'OUTPUT' or 'NONE'
        ],
        // Add more items as needed
    ];

            echo '<pre>';
           print_r($final_line_items);
           echo '</pre>';
    // Create the invoice data
    $invoice = [
        'Type' => 'ACCREC',  // 'ACCREC' for Accounts Receivable or 'ACCPAY' for Accounts Payable
        'Contact' => [
            'ContactID' => $xeroContactID,  
        ],
        'LineItems' => $final_line_items,  // Line items (array of items on the invoice)
        'Date' => date('Y-m-d'),
        'DueDate' => date('Y-m-d', strtotime("+$dueDays days")),  // Due date (default 30 days from now)
      //  'Reference' => $reference,  // Invoice reference (optional)
    ];
    
    try {
        // Make the POST request to create the invoice
        $response = $client->post('Invoices', [
            'json' => $invoice,  // Pass the invoice data as JSON
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