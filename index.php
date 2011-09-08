<?php
include "lib/clCLient.class.php";
include "lib/clQueries.php";

$client = new CLclient();
$query  = new CLReferencesQuery(CLReferencesQuery::TOWN_CONTEXT);

$query->getByCountry_code("kz");

$client->executeQuery($query);


echo $client->getAllResponse(true);
?>