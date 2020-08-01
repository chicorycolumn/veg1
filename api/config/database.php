<?php

if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

class Database
{
  private $use_clear_db = 0;

  private $username = "root";
  private $password = "";
  private $host = "localhost";
  private $db_name = "fruit_makhzan_db";
  public $table_name;
  public $connection;
  // $this->conn->exec("set names utf8");

  public function __construct()
  {
    if ($this->use_clear_db) {
      $this->username = "b4709ad1452782";
      $this->password = "7d6b0f7d";
      $this->host = "us-cdbr-east-02.cleardb.com";
      $this->db_name = "heroku_cb0feae1098e18e";
    }
  }

  public function makeConnection()
  {
    include "../../utils/get_gid.php";
    include "../../utils/make_table.php";
    //This fxn needs to START A NEW GAME.
    //So that means create a Gid, add that to Games table.
    //Then create NST and INV tables.

    $_SESSION["gid"] = $gid;

    $this->inv_table_name = $_SESSION["gid"] . "__INV";
    $this->nst_table_name = $_SESSION["gid"] . "__NST";

    $this->connection = mysqli_connect(
      $this->host,
      $this->username,
      $this->password,
      $this->db_name
    );

    if (!$this->connection) {
      echo "Error: Unable to connect to MySQL." . PHP_EOL;
      echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
      echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
      exit();
    }

    $table_name = $this->inv_table_name;
    $create_table_querystring = " (
      `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `name` varchar(255) NOT NULL,
      `quantity` int(11) NOT NULL,
      `selling_price` int(11) NOT NULL,
      `total_sales` int(11) DEFAULT 0,
      `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";
    $connection = $this->connection;
    $query_array = [
      "INSERT INTO " .
      $table_name .
      " (`name`, `quantity`, `selling_price`, `total_sales`) VALUES
      ('Morangines', 50, 5, 20)",

      "INSERT INTO " .
      $table_name .
      " (`name`, `quantity`, `selling_price`) VALUES
      ('Miwiwoos', 50, 5)",

      "INSERT INTO " .
      $table_name .
      " (`name`, `quantity`, `selling_price`) VALUES
      ('Misty Vistas', 50, 5)",

      "INSERT INTO " .
      $table_name .
      " (`name`, `quantity`, `selling_price`) VALUES
      ('My Old Man The Mango', 80, 4)",

      "INSERT INTO " .
      $table_name .
      " (`name`, `quantity`, `selling_price`) VALUES
      ('Moloko', 80, 4)",

      "INSERT INTO " .
      $table_name .
      " (`name`, `quantity`, `selling_price`) VALUES
      ('Manchurianos', 200, 100)",

      "INSERT INTO " .
      $table_name .
      " (`name`, `quantity`, `selling_price`) VALUES
      ('Matey-wateys', 30, 10)",
    ];

    make_table(
      $table_name,
      $create_table_querystring,
      $connection,
      $query_array
    );

    $table_name = $this->nst_table_name;
    $create_table_querystring = " (
      `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `name` varchar(255) NOT NULL,
      `stock_price` int(11) NOT NULL,
      `durability` int(11) NOT NULL,
      `popularity` int(11) DEFAULT 0
    )";
    $connection = $this->connection;
    $query_array = [
      "INSERT INTO " .
      $table_name .
      " (`name`, `stock_price`, `durability`, `popularity`) VALUES
      ('Funkalites', 5, 3, 8)",

      "INSERT INTO " .
      $table_name .
      " (`name`, `stock_price`, `durability`, `popularity`) VALUES
      ('Frangipanis', 10, 4, 10)",

      "INSERT INTO " .
      $table_name .
      " (`name`, `stock_price`, `durability`, `popularity`) VALUES
      ('Froobs', 1, 5, 7)",
    ];

    make_table(
      $table_name,
      $create_table_querystring,
      $connection,
      $query_array
    );
    mysqli_close($connection);
  }

  public function getConnection()
  {
    $this->connection = mysqli_connect(
      $this->host,
      $this->username,
      $this->password,
      $this->db_name
    );

    if (!$this->connection) {
      echo "Error: Unable to connect to MySQL." . PHP_EOL;
      echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
      echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
      exit();
    }

    return $this->connection;
  }

  public function closeConnection()
  {
    mysqli_close($this->connection);
  }
}
?>
