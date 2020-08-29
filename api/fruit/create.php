<?php

include_once '../config/database.php';
include_once '../objects/fruit_class.php';
include '../../utils/table_utils.php';

$database = new Database();
$db = $database->getConnection();

$fruit = new Fruit($db);
$fruit->name = filter_var($_POST['name'], FILTER_SANITIZE_SPECIAL_CHARS);
$name = filter_var($_POST['name'], FILTER_SANITIZE_SPECIAL_CHARS);
$popularity_factors = json_encode($_POST['popularity_factors']);
$max_prices = json_encode($_POST['max_prices']);
$rubicon = $_POST['rubicon'];
$table_name = $_POST['table_name'];
$identifying_column = "name";
$identifying_data = $_POST['name'];
$acronym = "s";
$get_full = false;

function go(
  $db,
  $fruit,
  $table_name,
  $name,
  $popularity_factors,
  $max_prices,
  $rubicon,
  $identifying_column,
  $identifying_data,
  $acronym,
  $get_full
) {
  if (
    !($result = $fruit->create_self(
      $table_name,
      $name,
      $popularity_factors,
      $max_prices,
      $rubicon
    ))
  ) {
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
      $acronym,
      $get_full
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

  if (!$result['data']->num_rows) {
    return [
      "status" => false,
      "message" =>
        "There are no rows from reading the db. The identifying data (" .
        $identifying_data .
        ") at identifying column (" .
        $identifying_column .
        ") does not correspond to anything in the table (" .
        $table_name .
        ").",
      "error" => $db->error,
    ];
  }

  if (!($fruit_arr = build_table_array($result["data"]))) {
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
  $name,
  $popularity_factors,
  $max_prices,
  $rubicon,
  $identifying_column,
  $identifying_data,
  $acronym,
  $get_full
);
$database->closeConnection();
print_r(json_encode($response));
?>
