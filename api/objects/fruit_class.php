<?php
if(session_status() == PHP_SESSION_NONE){session_start();}

class Fruit{
 
    private $conn;
    private $table_name = null;
    private $use_oop = true;
 
    public $id;
    public $name;
    public $quantity;
    public $selling_price;
    public $total_sales;
    public $created;
 
    public function __construct($dbwhole){
        $this->conn = $dbwhole->connection;
        $this->table_name = $dbwhole->table_name;

        if (isset($_SESSION['table_name'])){
            $this->table_name = $_SESSION['table_name'];
        } else {
            return "Error. No table is set.";
        }

    }

    public function execute_query($query, $conn){
        $res = $conn->query($query);
        if (!$res) {
            echo "failed to execute query: $query\n";
        } else {
            return $res;
        }
        if (is_object($res)) {
            $res->close();
        }
    }

    function read(){


        $query = 'SELECT * FROM '.$this->table_name;
        // $query = "INSERT INTO fruit(name, quantity, selling_price, total_sales) VALUES('dumbo', 5, 6, 7)";

        if ($this->use_oop){
            $res = $this->execute_query($query, $this->conn);
        }else{
            $res = mysqli_query($this->conn, $query);
        }
        // 
        // $query = "SELECT
        //             `id`, `name`, `quantity`, `selling_price`, `total_sales`, `created`
        //         FROM
        //             " . $this->table_name . " 
        //         ORDER BY
        //             id DESC";

        // $stmt = $this->execute_query($query, $this->conn)
    
        // $stmt = $this->conn->prepare($query);
        // $stmt->execute();
        return $res;
    }

    function read_single(){

        $stmt = $this->conn->prepare(
            "SELECT
        `id`, `name`, `quantity`, `selling_price`, `total_sales`, `created`
            FROM
         " . $this->table_name . " 
            WHERE
         id=?"
        );

        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        return $result; 
    }

    function create_self(){
        if($this->does_entry_exist()){
            return false;
        }
        
        $query = "INSERT INTO  ". $this->table_name ." 
                        ( `name`, `quantity`, `selling_price`)
                  VALUES
                        ('".$this->name."', '".$this->quantity."', '".$this->selling_price."')";

        $stmt = $this->conn->prepare($query);
        if($stmt->execute()){
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    function update_self(){

        $query = "UPDATE
                    " . $this->table_name . "
                SET
                    name='".$this->name."', quantity='".$this->quantity."', selling_price='".$this->selling_price."'
                WHERE
                    id='".$this->id."'";
    
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()){
            return $stmt;
        }
        return false;
    }

    function restock_self($new_quantity){

        $stmt = $this->conn->prepare(
            
            "UPDATE
            " . $this->table_name . "
        SET
            quantity=?
        WHERE
            id=?"
        );

        $stmt->bind_param("ii", $new_quantity, $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result; 
    }

    function delete_self(){

        $query = "DELETE FROM
                    " . $this->table_name . "
                WHERE
                    id= '".$this->id."'";
        
        $stmt = $this->conn->prepare($query);
        if($stmt->execute()){
            return true;
        }
        return false;
    }

    function does_entry_exist(){
        $query = "SELECT *
            FROM
                " . $this->table_name . " 
            WHERE
                name='".$this->name."'";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        if($stmt->rowCount() > 0){
            return true;
        }
        else{
            return false;
        }
    }
}