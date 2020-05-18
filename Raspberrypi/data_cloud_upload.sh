#!/bin/sh
endpoint=s3.us-south.cloud-object-storage.appdomain.cloud
bucket_name=cos-standard-26y
object_key=dataset4.csv
content_type='text/csv'

resource_instance_id="crn:v1:bluemix:public:cloud-object-storage:global:a/0ac5bac1308c446dbf195ac7fecfc483:edd01d65-543b-4e40-a30d-adfb1e95e8cc::"

API_KEY=WhjE6gWtt4T-wgUtz7tU14uhAti0VefYWyCrgxKoZpGR

token_data=$(curl -X "POST" "https://iam.cloud.ibm.com/identity/token" \
     -H 'Accept: application/json' \
     -H 'Content-Type: application/x-www-form-urlencoded' \
     --data-urlencode "apikey=$API_KEY" \
     --data-urlencode "response_type=cloud_iam" \
     --data-urlencode "grant_type=urn:ibm:params:oauth:grant-type:apikey")

access_token=$(python read_access_token.py "$token_data")

#echo $token_data
#echo "token****"
#echo $access_token

curl -X "PUT" "https://$endpoint/$bucket_name/$object_key" \
 -H "Authorization: bearer $access_token" \
 -H "Content-Type: $content_type" \
 --data-binary @dataset4.csv

echo "file uploaded",$(date +"%Y-%m-%d %T")
