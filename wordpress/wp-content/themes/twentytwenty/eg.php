<?php
//
// CEN 5035 Software Engineering
// Dr. Shihong Huang | Section 003 | CRN 16855 | Fall 2019
// Group 4 Project: EnergyGuard 
//

// For debugging.
//ini_set('display_errors', 1);
//error_reporting(E_ALL|E_STRICT);

//
// GLobal Variables
//
global $g_sqlEndpoint;
global $g_instanceCRN;
global $g_apiKey;
global $g_cosEndpoint;
global $g_cosBucketName;

// Permissions needed for the API KEY:
// Cloud Object Storage: Needs Reader+Writer access
// SQL Query: Needs Reader+Writer access

$g_apiKey = '<PLACE API KEY HERE>';
$g_cosEndpoint = 's3.us-south.cloud-object-storage.appdomain.cloud';
$g_cosBucketName = 'cos-standard-26y';

$g_instanceCRN="crn:v1:bluemix:public:sql-query:us-south:a/0ac5bac1308c446dbf195ac7fecfc483:f21eea81-c7de-41fe-b84d-346a4edcb290::";
$g_sqlEndpoint = "https://api.sql-query.cloud.ibm.com/v2/sql_jobs";

//
// Functions
//

// eg_get_access_token: Requests a Bearer access token from the IBM Cloud services.
// Returns a boolean indicating success.
// On success, returns a Bearer access token in the AccessToken output parameter.
function eg_get_access_token(&$accessToken) {
    global $g_apiKey;

    $ok = false;

    $accessToken = "";

    $http = new WP_Http;

    $result = $http->post("https://iam.cloud.ibm.com/identity/token", 
        array(
            'headers' => array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'apikey' => urlencode($g_apiKey),
                'response_type' => 'cloud_iam',
                'grant_type' => 'urn:ibm:params:oauth:grant-type:apikey',
            ),
        ));

    if (!is_wp_error($result)) {
        if ($result['response']['code'] == 200) {
            $json = $result['body'];
            $map = json_decode($json, true);
            $accessToken = 'Bearer ' . $map['access_token'];        
            $ok = true;
        }
    }

    return $ok;
}

// eg_cos_get_object: Retrieves the data stored in an COS Object.
// Returns a boolean indicating success.
// Parameters:
//   endpoint - the COS Service Endpoint
//   bucketName - the Bucket Name
//   objectKey - identifies the Object
//   accessToken - Bearer access token
//   response - (out) the returned object data
// On success, returns the object data in the Response output parameter.
function eg_cos_get_object($endpoint, $bucketName, $objectKey, $accessToken, &$response) {
    $ok = false;
    $response = "";

    $http = new WP_Http;

    $url = sprintf("https://%s/%s/%s", $endpoint, $bucketName, $objectKey);

    $result = $http->get($url,
        array(
            'headers' => array(
                'Authorization' => $accessToken
            )
        ));

    if (!is_wp_error($result)) {
        if ($result['response']['code'] == 200) {
            $response = $result['body'];
            $ok = true;
        }
    }

    return $ok;
}

// eg_cos_list_objects: Lists the objects in the COS Bucket.
// Returns a boolean indicating success.
// Parameters:
//   endpoint - the COS Service Endpoint
//   bucketName - the Bucket Name
//   accessToken - Bearer access token
//   response - (out) the object list
// On success, returns the object list in the Response output parameter.
function eg_cos_list_objects($endpoint, $bucketName, $accessToken, &$response) {
    $ok = false;
    $response = "";

    $http = new WP_Http;

    $url = sprintf("https://%s/%s", $endpoint, $bucketName);

    $result = $http->get($url,
        array(
            'headers' => array(
                'Authorization' => $accessToken,
            )
        ));

    //echo "result="; var_dump($result); echo "<br>";

    if (!is_wp_error($result)) {
        if ($result['response']['code'] == 200) {
            $response = $result['body'];
            $ok = true;
        }
    }

    return $ok;
}

/*
Example of CURL command-line for SQL Query Service:
curl -XPOST \
  --url "https://api.sql-query.cloud.ibm.com/v2/sql_jobs?instance_crn=YOUR_SQL_QUERY_CRN" \
 -H "Accept: application/json" \
 -H "Authorization: Bearer YOUR_BEARER_TOKEN" \
 -H "Content-Type: application/json" \
 -d '{"statement":"SELECT firstname FROM cos://us-geo/sql/employees.parquet STORED AS PARQUET WHERE EMPLOYEEID=5 INTO cos://us-geo/target-bucket/q1-results" }'
*/

/* 
Example of Response JSON object var_dumped:
jobID=string(36) "d4fd4951-9f24-413e-aef1-ffc5b820ee56" 
status=string(6) "queued" 
HTTP STATUS CODE 401
*/

// eg_sql_query: Submits a query to the IBM SQL Query Service.
// Returns a boolean indicating success.
// Parameters:
//   endpoint - the SQL Query Service Endpoint
//   instanceCRN - the Cloud Resource Name of the SQL Query Service Instance
//   accessToken - Bearer access token
//   query - IBM SQL Query string
//   jobID - (out) the SQL job ID
//   status - (out) the status of the SQL query
// On success, returns the Job ID and Status in output parameters.
function eg_sql_query($endpoint, $instanceCRN, $accessToken, $query, &$jobID, &$status) {
    global $g_sqlEndpoint;

    $ok = false;

    $http = new WP_Http;

    $url = sprintf("%s?instance_crn=%s", $endpoint, $instanceCRN);

    //echo "url="; var_dump($url); echo "<br>";

    $result = $http->post($url, 
        array(
            'headers' => array(
                'Accept' => 'application/json',
                'Authorization' => $accessToken,
                'Content-Type' => 'application/json',
            ),
            'data_format' => 'body',
            'body' => json_encode(array(
                    'statement' => $query
                )),
        ));

    //echo "result="; var_dump($result); echo "<br>";

    if (!is_wp_error($result)) {
        $code = $result['response']['code'];
        if ($code == 200 || $code == 201) {
            $json = $result['body'];
            $map = json_decode($json, true);
            $jobID = $map['job_id'];
            $status = $map['status'];
            $ok = true;
        }
    }

    return $ok;
}

// eg_sql_poll: Polls the IBM SQL Query Service for the status of a Job.
// Returns a boolean indicating success.
// Parameters:
//   endpoint - the SQL Query Service Endpoint
//   jobID - the SQL job ID
//   instanceCRN - the Cloud Resource Name of the SQL Query Service Instance
//   accessToken - Bearer access token
//   status - (out) the status of the SQL query
//   resultSet - (out) the Location of the result set
// On success, returns the Job ID and Status in output parameters.
function eg_sql_poll($endpoint, $jobID, $instanceCRN, $accessToken, &$status, &$resultSet) {
    global $g_sqlEndpoint;

    $ok = false;

    $http = new WP_Http;

    $url = sprintf("%s/%s?instance_crn=%s", $endpoint, $jobID, $instanceCRN);

    //echo "url="; var_dump($url); echo "<br>";

    $result = $http->get($url, 
        array(
            'headers' => array(
                'Authorization' => $accessToken,
            ),
        ));

    //echo "result="; var_dump($result); echo "<br>";

    if (!is_wp_error($result)) {
        $code = $result['response']['code'];
        if ($code == 200) {
            $json = $result['body'];
            $map = json_decode($json, true);
            $status = $map['status'];
            $resultSet = $map['resultset_location'];
            $ok = true;
        }
    }

    return $ok;
}

function eg_sql_slurp($endpoint, $instanceCRN, $cosEndpoint, $cosBucketName, $accessToken, $query, $objectKey, $extension, &$result) {
    $ok = false;
    $jobID = "";
    $status = "";

    if (! ($extension == 'csv' || $extension == 'json'))
        return false;
    
    $ok = eg_sql_query($endpoint, $instanceCRN, $accessToken, $query, $jobID, $status);    
    
    //echo "result of eg_sql_query:<br>";
    //echo "ok="; var_dump($ok); echo "<br>";
    //echo "jobID="; var_dump($jobID); echo "<br>";
    //echo "status="; var_dump($status); echo "<br>";
    
    if (!$ok) return false;
    
    ///////////////// SQL POLL
    
    do {
        $status = "";
        $resultSet = "";
        $ok = eg_sql_poll($endpoint, $jobID, $instanceCRN, $accessToken, $status, $resultSet);
        if (!$ok)
            break;

        if ($status == "queued" || $status == "running") {
            continue; // Normal
        } else if ($status == "failed" || $status != "completed") {
            $ok = false; // ABNORMAL
            break;
        }

        usleep(1000); // 1 millisecond sleep (the I/O probably takes a whole lot more time so CPU will be sleeping during the I/O)       
    }
    while ($status != "completed");
    
    //echo "result of eg_sql_poll:<br>";
    //echo "ok="; var_dump($ok); echo "<br>";
    //echo "status="; var_dump($status); echo "<br>";
    //echo "resultSet="; var_dump($resultSet); echo "<br>";
    
    if (!$ok) return false;
    
    $url = parse_url($resultSet);
    //echo "url="; var_dump($url); echo "<br>";
    
    
    // Enumerate all the objects. Find the result set file.
    $response = "";
    $ok = eg_cos_list_objects($cosEndpoint, $cosBucketName, $accessToken, $response);
    if ($ok) {
    
        //echo "result of eg_cos_list_objects:<br>";
        //echo "response="; var_dump($response); echo "<br>";
    
        $matches = array();
        $pattern = '/' . $objectKey . '\/jobid=' . $jobID . '\/(part-[-a-f0-9]+-attempt_[a-z0-9_]+\.' . $extension . ')/';
    
        //echo "pattern="; var_dump($pattern); echo "<br>";
    
        $found = preg_match($pattern, $response, $matches);
    
        //echo "result of preg_match:<br>";
    
        if (!($found === false)) {
    
            //echo "found=true<br>";
            //echo "matches="; var_dump($matches); echo "<br>";
            //echo "url="; var_dump($url); echo "<br>";
    
            if ($url['scheme'] == 'cos' &&
                $url['host'] == 's3.us-south.objectstorage.softlayer.net' &&
                $url['path'] == '/' . $cosBucketName . '/' . $objectKey . '/jobid=' . $jobID) {
    
                $file = $matches[1];
                $ok = eg_cos_get_object($cosEndpoint, $cosBucketName, $objectKey . '/jobid=' . $jobID . '/' . $file, $accessToken, $response);
    
                //echo "result of eg_cos_get_object:<br>";
                //echo "ok="; var_dump($ok); echo "<br>";
                //echo "response="; var_dump($response); echo "<br>";  
                if ($ok) {
                    $result = $response;
                }  
            }
        }        
    }   
    return $ok;
}

// spit: Writes the a string, Data, to the named file.
// Returns a boolean indicating success or failure.
function spit($filename, $data) {
    $ok = false;
    $outputFile = fopen($filename, "w");
    if ($outputFile) {
        fprintf($outputFile, "%s", $data);
        fclose($outputFile);
        $ok = true;
    }
    return $ok;
}

// Execs Report1 SQL query and writes results to a CSV file.
function doReport1CSV($accessToken) {
    global $g_sqlEndpoint, $g_instanceCRN, $g_cosEndpoint, $g_cosBucketName;
    
    $queryReport1 = <<<EOD
    --Report1: Hourly Energy Report for one Day
    --Sort by hour in ascending order for Device1 dataset
    WITH dataset AS (
        SELECT DeviceID, KilowattHours, CAST(DATE_FORMAT(TStamp, 'H') AS int) AS hours
            FROM cos://us-south/cos-standard-26y/dataset1.csv STORED AS CSV
    )
    SELECT hours, FORMAT_NUMBER(CAST(SUM(KilowattHours) AS float),3) AS kWhSum
        FROM dataset 
        GROUP BY hours
        ORDER BY hours ASC
        INTO cos://us-south/cos-standard-26y/Report1_Device1_output.csv JOBPREFIX JOBID STORED AS CSV
    EOD;
    
    $result = "";
    $ok = eg_sql_slurp($g_sqlEndpoint, $g_instanceCRN, $g_cosEndpoint, $g_cosBucketName, $accessToken, $queryReport1, 'Report1_Device1_output.csv', 'csv', $result);
    //echo "Result of eg_sql_slurp:<br>";
    //echo "ok="; var_dump($ok); echo "<br>";
    //echo "result="; var_dump($result); echo "<br>";
    //echo "<p>QUERY TO CSV:</p><p>$result</p>";
    
    $filename = '/var/www/html/wordpress/wp-content/uploads/2019/12/Report1_Device1_output.csv';
    if ($ok) {
        spit($filename, $result);
    }    
}

// Execs Report1 SQL query and writes results to a JSON file.
function doReport1JSON($accessToken) {
    global $g_sqlEndpoint, $g_instanceCRN, $g_cosEndpoint, $g_cosBucketName;
    
    $queryReport1 = <<<EOD
    --Report1: Hourly Energy Report for one Day
    --Sort by hour in ascending order for Device1 dataset
    WITH dataset AS (
        SELECT DeviceID, KilowattHours, CAST(DATE_FORMAT(TStamp, 'H') AS int) AS hours
            FROM cos://us-south/cos-standard-26y/dataset1.csv STORED AS CSV
    )
    SELECT hours, FORMAT_NUMBER(CAST(SUM(KilowattHours) AS float),3) AS kWhSum
        FROM dataset 
        GROUP BY hours
        ORDER BY hours ASC
        INTO cos://us-south/cos-standard-26y/Report1_Device1_output.json STORED AS JSON
    EOD;
    
    $result = "";
    $ok = eg_sql_slurp($g_sqlEndpoint, $g_instanceCRN, $g_cosEndpoint, $g_cosBucketName, $accessToken, $queryReport1, 'Report1_Device1_output.json', 'json', $result);
    //echo "<p>QUERY TO JSON:</p><p>$result</p>";
    
    $filename = '/var/www/html/wordpress/wp-content/uploads/2019/12/Report1_Device1_output.json';
    if ($ok) {
        $result = "[". PHP_EOL . join("," . PHP_EOL, preg_split('/\s+/', $result));
        $result = preg_replace('/,$/', '', $result);
        $result = $result . "]" . PHP_EOL;
        spit($filename, $result);
    }    
}


// Execs Report2 SQL query and writes results to a CSV file.
function doReport2CSV($accessToken) {
    global $g_sqlEndpoint, $g_instanceCRN, $g_cosEndpoint, $g_cosBucketName;

    // dataset4.csv is live data from Raspberry Pi
    $query = <<<EOD
    SELECT * FROM cos://us-south/cos-standard-26y/dataset4.csv STORED AS CSV
    INTO cos://us-south/cos-standard-26y/Report2_Device2_output.csv JOBPREFIX JOBID STORED AS CSV
    EOD;
    
    $result = "";
    $ok = eg_sql_slurp($g_sqlEndpoint, $g_instanceCRN, $g_cosEndpoint, $g_cosBucketName, $accessToken, $query, 'Report2_Device2_output.csv', 'csv', $result);
    //echo "Result of eg_sql_slurp:<br>";
    //echo "ok="; var_dump($ok); echo "<br>";
    //echo "result="; var_dump($result); echo "<br>";
    //echo "<p>QUERY TO CSV:</p><p>$result</p>";
    
    $filename = '/var/www/html/wordpress/wp-content/uploads/2019/12/Report2_Device2_output.csv';
    if ($ok) {
        spit($filename, $result);
    }    
}


//
// Main Program
//

function eg_init() {
///////////////// ACCESS TOKEN

$ok = eg_get_access_token($accessToken);

//echo "result of eg_get_access_token:<br>";
//echo "ok=";  var_dump($ok); echo "<br>";
//echo "accessToken="; var_dump($accessToken); echo "<br>";

///////////////// SQL QUERY
doReport1CSV($accessToken);
doReport2CSV($accessToken);
}

?>
