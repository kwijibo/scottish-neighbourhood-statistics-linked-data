<?php

$reader = new XMLReader();

$reader->open('ig_lookup.xml');
$php = array();
while ($reader->read()) {
    switch ($reader->nodeType) {
        case (XMLREADER::ELEMENT):
        if ($reader->localName == "indicator") {
          $node = $reader->expand();
          if($node->getElementsByTagName('code')->item(0)->textContent == 'CS-IGLAcodes'){
            $areas = $node->getElementsByTagName('area');
            foreach($areas as $area){
              if($area->hasAttribute('code')){
                $IG = $area->getAttribute('code');
                $LA = $area->textContent;
                $php[$IG]=$LA;
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
file_put_contents('IG_LA_lookup.serialised.php', serialize($php));
?>
