#!/bin/bash


echo "Downloading csv files"
echo "~~~~~~~~~~~~~~~~~~~~~~~~";
curl -v http://www.sns.gov.uk/BulkDownloads/SNS_FullData_CSV_5_9_2011.zip > input-data/FullCSV.zip
echo "unzipping folder..."
unzip input-data/FullCSV.zip -d input-data/FullCSV
rm input-data/FullCSV.zip
echo "Running supplementary conversions ..."
echo
echo "Converting indicator metadata ..."
php indicator-metadata.csv.php > output-data/indicator-metadata.nt
echo "converting postcodes ...."
php postcode_dz_ig.php > output-data/postcode_dz_ig.nt
php mmw-convert.php > output-data/mmw_labels.nt
php mmw_dz_ig.php > output-data/mmw_dz_ig.nt
php SIMD_geography.php > output-data/simd_geography.nt
php geographies.php
echo "Converting local authorities"
php local-authority-sameAs.php > output-data/la-sameAs.nt

echo "Compiling ntriples files"

ls output-data/*.nt | while read line ; 
do
    echo "> $line >" 
    cat compilation/all.nt "$line" > compilation/all.nt  ; 
done

php  convert-csv-files.php

echo "Compiling indicator files"

ls output-data/from-csv/ | while read line ; 
do 
    echo "$line"
    cat compilation/all.nt "$line" > compilation/all.nt  ; 
done



