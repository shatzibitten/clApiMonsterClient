<?php
include "lib/clCLient.class.php";
include "lib/clQueries.php";

header ('Content-type: text/html; charset=utf-8'); 

$client = new clClient();
$query  = new clReferencesQuery(clReferencesQuery::TOWN_CONTEXT);


$query->getByCountry_code("kz")
      ->getById(1);

//after calling this method, you cannot make any changes
$query->freeze();

if ($query->validateQuery())
 {
  $client->executeQuery($query);
  echo $client->getAllResponse("raw"); //for correct xml view in raw mode change Content-type to text/xml

 }
else
 {
  die("Please init these parameters: ".implode(",",$query->getRequiredParams()));
 }
?>