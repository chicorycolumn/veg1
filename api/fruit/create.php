<?php

include_once '../config/database.php';
include_once '../objects/fruit_class.php';
include '../../utils/table_utils.php';

$database = new Database();
$db = $database->getConnection();

$fruit = new Fruit($db);
$fruit->name = $_GET['name'];
$fruit->quantity = $_GET['quantity'];
$fruit->selling_price = array_key_exists('selling_price', $_GET)
  ? $_GET['selling_price']
  : 0;
$table_name = $_GET['table_name'];
$identifying_column = "name";
$identifying_data = $_GET['name'];
$acronym = "s";

function go(
  $db,
  $fruit,
  $table_name,
  $identifying_column,
  $identifying_data,
  $acronym
) {
  if (!($result = $fruit->create_self($table_name))) {
    return [
      "status" => false,
      "message" => "Error when calling Sfruit->create_self.",
      "error" => $db->error,
    ];
  }

  if (!$result["status"]) {
    return $result;
  }

  if (
    !($result = $fruit->read_single(
      $table_name,
      $identifying_column,
      $identifying_data,
      $acronym
    ))
  ) {
    return [
      "status" => false,
      "message" => "Error when calling Sfruit->read_single.",
      "error" => $db->error,
    ];
  }

  if (!$result["status"]) {
    return $result;
  }

  if (!($fruit_arr = build_table_array($table_name, $result["data"]))) {
    return [
      "status" => false,
      "message" => "Error in build_table_array. 1cre",
      "error" => $db->error,
    ];
  }

  return [
    "status" => true,
    "data" => $fruit_arr,
  ];
}

$response = go(
  $db,
  $fruit,
  $table_name,
  $identifying_column,
  $identifying_data,
  $acronym
);
$database->closeConnection();
print_r(json_encode($response));
?>
