<?php

include_once '../config/database.php';
include_once '../objects/fruit_class.php';

$database = new Database();
$db = $database->getConnection();

$fruit = new Fruit($db);
$fruit->id = $_GET['id'];
$table_suffix = $_GET['table'];

function go($db, $fruit, $table_suffix)
{
  if (!($result = $fruit->delete_self($table_suffix))) {
    return [
      "status" => false,
      "message" => "Error when calling Sfruit->delete_self.",
      "error" => $db->error,
    ];
  }
  return $result;
}

$response = go($db, $fruit, $table_suffix);
$database->closeConnection();
print_r(json_encode($response));
?>
