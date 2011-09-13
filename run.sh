#!/bin/bash

echo "Downloading XML files"
echo "~~~~~~~~~~~~~~~~~~~~~~~~";
curl -v http://www.sns.gov.uk/BulkDownloads/SNS_FullData_XML_5_9_2011.zip > input-data/FullXML.zip
echo "unzipping folder..."
unzip -of input-data/FullXML.zip -d input-data/FullXML
#rm input-data/FullXML.zip
