<?php

$reader = new XMLReader();

$reader->open('input-data/datazone_to_la_lookup.xml');
$php = array();
while ($reader->read()) {
    switch ($reader->nodeType) {
        case (XMLREADER::ELEMENT):
        if ($reader->localName == "indicator") {
          $node = $reader->expand();
          if($node->getElementsByTagName('code')->item(0)->textContent == 'CS-LAcodes'){
            $areas = $node->getElementsByTagName('area');
            foreach($areas as $area){
              if($area->hasAttribute('code')){
                $DZ = $area->getAttribute('code');
                $LA = $area->textContent;
                $php[$DZ]=$LA;
              }
            }
          }
        }
        break;
    } 
}
echo '$datazone_la_lookup = ';
var_export($php);
echo ';';
file_put_contents('DZ_LA_lookup.serialised.php', serialize($php));
?>
