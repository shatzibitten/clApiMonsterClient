<?php
include "lib/clCLient.class.php";
include "lib/clQueries.php";

$client = new clClient();
$query  = new clReferencesQuery(clReferencesQuery::TOWN_CONTEXT);

$query->getByCountry_code("kz");

$client->executeQuery($query);


echo $client->getAllResponse(true);
?>