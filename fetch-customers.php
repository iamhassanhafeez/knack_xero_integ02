<?php

// =============================== Knack Configuration =======================
$CustomersTableEndPoint = 'https://api.knack.com/v1/objects/object_1/records';
$api_key = '5731568a-75ed-4a6e-b906-7c3cda415405';
$app_id = '64ec0e7df4070c0028ff4a07';



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