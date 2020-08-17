<?php
include_once '../api/config/database.php';
include '../utils/table_utils.php';
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
delete_manipulated_cookie();

if (!isset($_SESSION['gid'])) {
  header("Location: ../home");
  exit();
}

$show_dev_data = false;

setcookie("makhzan", $_SESSION['gid'], time() + 3600 * 24 * 30, "/");
$gid = $_SESSION['gid'];
$inv_table_name = $_SESSION['inv_table_name'];
$animal = "doggy";

function update_timestamp()
{
  $database = new Database();
  $db = $database->getConnection();
  $result = update_row(
    $db,
    "last_accessed",
    time(),
    "game_id",
    $_SESSION['gid'],
    "games",
    "is"
  );
  $database->closeConnection();
  return $result;
}

$result = update_timestamp();

if (!$result) {
  echo "Error in updating game's timestamp.";
  die();
}
?>

<?php
echo "<link rel='stylesheet' type='text/css' href='../css/playIndex.css' />";
include 'content/mainStats.php';
include 'content/mainBulletin.php';
include 'content/mainButton.php';
include 'content/invTable.php';

if ($show_dev_data) {
  $_SESSION['show_dev_data'] = 1;
  $content =
    '<h1> 
  ' .
    $gid .
    '
  </h1>   

  <button onClick="checkSession()">Check Session</button>
  <button onClick="checkTCs()">Check TCs and Seed Data</button>';
} else {
  $_SESSION['show_dev_data'] = 0;
  $content = "";
}

$content .=
  '
  <div class="mainDiv">
    ' .
  $mainStats .
  '
  </div>

  <div class="mainDiv">
    ' .
  $mainBulletin .
  '
  </div>

  <div class="mainDiv">
    ' .
  $mainButton .
  '
  </div>
    
  <div class="mainDiv mainDivTable1">
    ' .
  $invTable .
  '
  </div>
  ';

include '../master.php';
?>


<script>
//' For some reason this apostrophe is necessary.
let seed_data = <?php print_r(json_encode($_SESSION['seed_data'])); ?>

let day_costs = 0;
let week_record = {}

let trend_calculates = {
  "weather": parseInt(`<?php print_r(
    ((array) json_decode($_SESSION['trend_calculates']))['weather']
  ); ?>`),
  "love": parseInt(`<?php print_r(
    ((array) json_decode($_SESSION['trend_calculates']))['love']
  ); ?>`),
    "politics": parseInt(`<?php print_r(
      ((array) json_decode($_SESSION['trend_calculates']))['politics']
    ); ?>`),
  "conformity": parseInt(`<?php print_r(
    ((array) json_decode($_SESSION['trend_calculates']))['conformity']
  ); ?>`),
    "decadence": parseInt(`<?php print_r(
      ((array) json_decode($_SESSION['trend_calculates']))['decadence']
    ); ?>`),
    "conformity_history": parseInt(`<?php print_r(
      ((array) json_decode($_SESSION['trend_calculates']))['conformity_history']
    ); ?>`),
}

fillInvTable()
updateGameStats(
  "<?php echo $_SESSION['money_stat']; ?>", 
  "<?php echo $_SESSION['days_stat']; ?>",
  null
)

let columnIndexRef = getColumnIndexes()

function newDay() {

  let num_rows = $("table#inventory tbody tr").filter(function () {
    return this;
  }).length;
  let incipient_sales = calculateSales();
  let day_profit = Object.values(incipient_sales).reduce(
    (sum, obj) => sum + obj.profit,
    0
  );

  let days = parseInt($("#daysStat").text())

  if (days % 7 == 0){week_record = {}}
  week_record[days+1] = {profit: day_profit, costs: day_costs}

  fillQuantityYesterday(incipient_sales); //Moves current quantities to the qy column.
  updateGamesTable(day_profit, "new day", week_record); //Increments Money and Days. Also updates displayed table new Pop and Mxb.
  updateInventoryTable(incipient_sales); //Reduces quantities by sold amounts.

  day_costs = 0

  setTimeout(() => {
    checkTCs()
  }, 500);
}

function fillQuantityYesterday(incipient_sales) {
  let num_rows = $("table#inventory tbody tr").filter(function () {
    return this;
  }).length;
  for (let i = 0; i < num_rows; i++) {
    let row = $("table#inventory tbody tr:eq(" + i + ")");
    let name = row.children().eq(getColumnIndexes()['name']).text()
    
    row
      .find(".qy")
      .text(incipient_sales[name]['sales_quantity'] || 0);
  }
}

function updateGamesTable(money_crement, operation, week_record) {
  
  if (operation == "new day") {
    console.log("week_record", week_record)
    $.ajax({
      type: "GET",
      url: "../api/fruit/new_day_supertable.php",
      dataType: "json",
      data: {
        table_name: "games",
        identifying_column: "game_id",
        identifying_data: `<?php echo $_SESSION['gid']; ?>`,
        profit: money_crement,
        week_record: week_record,
        json_column: "overall_sales_history"
      },
      error: function (result) {
        console.log("An error occurred immediately in $.ajax request.", result);
        console.log(result.responseText);
      },
      success: function (result) {

        if (result["status"]) {

          let { money_stat, days_stat, trend_calculates } = result[
            "update_data"
          ];
          updateGameStats(money_stat, days_stat, trend_calculates);
        } else {
          console.log(result["message"], result["error"]);
        }
      },
    });
  } else if ((operation = "restock" || operation == "decrement")) {

    $.ajax({
      type: "GET",
      url: "../api/fruit/update.php",
      dataType: "json",
      data: {
        table_name: "games",
        identifying_column: "game_id",
        identifying_data: `<?php echo $_SESSION['gid']; ?>`,
        acronym: "is",
        update_data: {
          money_stat: parseInt($("#moneyStat").text()) - money_crement,
        },
        should_update_session: true,
      },
      error: function (result) {
        console.log(
          "An error occurred immediately in this $.ajax request.",
          result
        );
        console.log(result.responseText);
      },
      success: function (result) {
        console.log(result);
        if (result["status"]) {

          let { money_stat } = result["update_data"];
          updateGameStats(money_stat);
        } else {
          console.log(result["message"], result["error"]);
        }
      },
    });
  }
}

function updateInventoryTable(incipient_sales) {

  $.ajax({
    type: "POST",
    url: "../api/fruit/new_day_subtable.php",
    dataType: "json",
    data: {
      table_name: `<?php echo $_SESSION['inv_table_name']; ?>`,
      column_to_change: "quantity",
      new_data_key: "sales_quantity",
      identifying_column: "name",
      operation: "decrement",
      data_obj: incipient_sales,
      data_type: "i",
    },
    error: function (result) {
      console.log(
        "###An error occurred immediately in $.ajax request.",
        result
      );
      console.log(result.responseText);
    },
    success: function (result) {

      if (result["status"]) {
        let names = Object.keys(result["update_data"]);

        names.forEach((name) => {
          let row = $("table#inventory tr").filter(function () {
            return $(this).children().eq(columnIndexRef.name).text() == name;
          });

          let current_quantity = parseInt(
            row.find(".quantity").text()
          );
          let new_quantity =
            current_quantity -
            parseInt(result["update_data"][name]["sales_quantity"]);
          row.find(".quantity").text(new_quantity);
        });
        verifyBuyButtons()
      } else {
        console.log(result["message"], result["error"]);
      }
    },
  });
}

function fillInvTable(shouldWipe) {
  if (shouldWipe) {
    $("#inventory tbody > tr").remove();
  }
  $.ajax({
    type: "GET",
    url: "../api/fruit/read.php",
    dataType: "json",
    data: {
      table_name: "<?php echo $inv_table_name; ?>",
      get_full: false,
    },
    error: function (result) {
      console.log("An error occurred immediately in $.ajax request.", result);
      console.log(result.responseText);
    },
    success: function (result) {

      if (result["status"]) {
        result["data"].forEach((fruit) => {
          let response = "";
          let {
            id,
            name,
            quantity,
            selling_price,
            resilience,
            max_prices,
            popularity_factors,
          } = fruit;
          let formattedName = name.replace(/\s/g, "%20");
          let {
            popularity,
            max_buying_price,
            restock_price,
          } = getSalesSubstrates(
            popularity_factors,
            max_prices,
            trend_calculates
          );
                    response += 
                    "<tr id='"+formattedName+"'>"+


                      "<td class='regularTD'>"+name+"</td>"+


                      "<td class='regularTD quantityTD'>"+
                      "<div class='invData'>"+
                        "<p class='quantity noMarginPadding'>"+quantity+"</p>"+
                        "<div style='margin-top: 10px;'>"+
                        "<span class='noMarginPadding qyText'>SALES: </span>"+
                        "<span class='noMarginPadding qy'>~</span>"+
                        "</div>"+
                      "</div>"+
                      "</td>"+
                      
                      
                      "<td class='clickable highlighted regularTD sellingTD' "+
                      
                      "onClick=changeSellingPrice(true,'"+formattedName+"')>"+
                        
                        "<p class='shown sellingPriceSpan clickable'>"+selling_price+"</pb>"+
                        
                        "<form class='hidden sellingPriceForm' "+
                        "onsubmit=submitSellingPrice(`"+formattedName+"`) "+
                        "onfocusout=changeSellingPrice(false,'"+formattedName+"')>"+
                          
                          "<input class='sellingPriceInput' "+
                          "onkeypress='return validateSellingPriceInput(event)' "+
                          "maxlength=10 maxlength=10 type='text'>"+
                          
                          "<button type='submit' class='sellingPriceButton' "+
                          "onclick=submitSellingPrice(`"+formattedName+"`)>OK</button>"+
                        
                        "</form>"+
                      
                      "</td>"+
                      

                      "<td class='regularTD restockPriceTD'>"+
                        "<div class='popularityCircleSpan'></div>"+
                        "<span class='restockPriceSpan'>"+restock_price+"</span>"+
                      "</td>"+
                      

                      "<td class='regularTD' style='cursor:help;' "+
                      "onClick=printSingle('"+formattedName+"')>"+
                        "<p class='invData resilienceData'>"+resilience+"</p>"+
                      "</td>"+
                      

                      "<td class='buttonTD'>"+
                          
                          "<div class='buttonSuperHolder'>"+
                          printDevDataHTML(popularity, max_buying_price)+
                            
                            "<div class='buttonSubHolder'>"+
                              "<button class='smallButtonKind button2 buyButton' onClick=restockFruit('"+formattedName+"')>Buy</button>"+            
                              "<input value="+seed_data.filter(item => item['name']==name)[0]['restock_amount']+" "+
                                "class='amountInput amountInput_restock' "+
                                "onclick=this.select() "+
                                "onkeypress='return /[0-9]/.test(event.key)' "+
                                "onkeyup=setAmount('"+formattedName+"','restock') "+
                                "onblur=setAmount('"+formattedName+"','restock') "+
                                "maxlength=10>"+
                            "</div>"+ 
                            
                            "<div class='buttonSubHolder'>"+
                              "<button class='smallButtonKind button3' "+
                                "onclick=setAmount('"+formattedName+"','restock','max') "+
                              ">MAX</button>"+
                              "<button class='smallButtonKind button4' "+
                                "onclick=setAmount('"+formattedName+"','restock','increment') "+
                              ">⇧</button>"+
                              "<button class='smallButtonKind button4' "+
                                "onclick=setAmount('"+formattedName+"','restock','decrement') "+
                              ">⇩</button>"+
                            "</div>"+                            
                          
                          "</div>"+
                      
                      "</td>"+
                    
                    "</tr>";
                    
                    $(response).appendTo($("#inventory"));
                    
                  
        });
                    setTimeout(() => {
                      bindUsefulJqueriesAfterLoadingDataIntoTable()
                    }, 500);
                    
                    updateSalesSubstratesInDisplayedTable()

                    verifyBuyButtons()



      } else {
        console.log(result["message"], result["error"]);
      }
    },
  });
}

function verifyBuyButtons(){
  console.log("Money", parseInt($("#moneyStat").text()))
  
  $(".buyButton").each(function(){

    let name = $(this).parents("tr").children().eq(getColumnIndexes()['name']).text()
    let restockPrice = parseInt($(this).parents("tr").find(".restockPriceSpan").text())
    let restockQuantity = parseInt($(this).parents("tr").find(".amountInput_restock").val())
    let maxBuyableQuantity = Math.floor(parseInt($("#moneyStat").text()) / restockPrice)

    console.log({name, restockPrice, restockQuantity, maxBuyableQuantity})
  
    $(this).prop("disabled", restockQuantity > maxBuyableQuantity)
  })
}

function setAmount(formattedName, operation, modifier, forced_amount) {

  name = formattedName.replace(/%20/g, " ");

  let row = $("table#inventory tbody tr").filter(function () {
    return $(this).children().eq(getColumnIndexes()["name"]).text() == name;
  });

  let class_name = ".amountInput" + "_" + operation;
  let quantity = parseInt(row.find(".quantity").text());
  let restock_amount = parseInt(row.find(".amountInput_restock").val())
  let restock_price = parseInt(row.find(".restockPriceSpan").text())
  let throw_amount = parseInt(row.find(".amountInput_throw").val())
  let max_buyable_quantity = Math.floor(parseInt($("#moneyStat").text()) / restock_price)
  let key = operation + "_amount";
  let reset_value = restock_amount

  // console.log({name, class_name, quantity, restock_amount, restock_price, throw_amount, max_buyable_quantity, key})
  
  console.log("+++", restock_amount)

  if (forced_amount && operation == "restock"){
    console.log(111)
    restock_amount = forced_amount
  } if (forced_amount && operation == "throw"){
    console.log(222)
    restock_amount = throw_amount
  } if (operation == "restock" && (!restock_amount || !parseInt(restock_amount)) || operation == "throw" && (!throw_amount || !parseInt(throw_amount))) {
    console.log(333)
    reset_value = 1;
  } if (operation == "throw" && parseInt(throw_amount) > quantity) {
    console.log(444)
    reset_value = quantity || 1;
  } if (operation == "restock" && modifier && modifier == "max") {
    console.log(555)
    reset_value = max_buyable_quantity || 1
  } if (operation == "restock" && modifier && modifier == "increment") {
    console.log(666)
    reset_value = restock_amount < max_buyable_quantity ? restock_amount + 1 || 1 : restock_amount
  } if (operation == "restock" && modifier && modifier == "decrement") {
    console.log(777)
    reset_value = restock_amount - 1 || 1
  }
  seed_data.filter((item) => item["name"] == name)[0][key] = reset_value;
  row.find(class_name).val(reset_value);

  verifyBuyButtons()
    
    // } else {
      // $.ajax({
      //   type: "GET",
      //   url: "../utils/set_session.php",
      //   dataType: "json",
      //   data: {
      //     seed_data: seed_data,
      //   },
      //   error: function (result) {
      //     console.log(
      //       "An error occurred immediately in this $.ajax request.",
      //       result
      //     );
      //     console.log(result.responseText);
      //   },
      //   success: function (result) {
      //     if (result["status"]) {
      //       console.log("status true");
      //     } else {
      //       console.log(result["message"], result["error"]);
      //     }
      //   },
      // });
    // }
  
}

function validateSellingPriceInput(e){
  let k = e.keyCode
  let w = e.which

  function checkKey(key){
    return key == 13 || (key >= 48 && key <= 57)
  }

  return checkKey(k) || checkKey(w) 
}

function submitSellingPrice(name){

    event.preventDefault()

    name = name.replace(/%20/g, " ");
    let columnIndexRef = getColumnIndexes();
    let row = $("table#inventory tbody tr").filter(function () {
      return $(this).children().eq(columnIndexRef["name"]).text() == name;
    });
    let span = row.find(".sellingPriceSpan");
    let span_text = span.text();
    let form = row.find(".sellingPriceForm");
    let input = row.find(".sellingPriceInput");
    let button = row.find(".sellingPriceButton");

    let putative_price = input.val();

  if (!
  putative_price || !parseInt(putative_price)) {
    input.val("");
    form.addClass("hidden");
    span.removeClass("hidden");
    return;
  }

  if (putative_price.match(/^\d+$/)) {
    $.ajax({
      type: "GET",
      url: "../api/fruit/update.php",
      dataType: "json",
      data: {
        name: name,
        table_name: "<?php echo $inv_table_name; ?>",
        identifying_column: "name",
        identifying_data: name,
        update_data: {
          selling_price: putative_price,
        },
        acronym: "is",
        should_update_session: 0,
      },
      error: function (result) {
        console.log("An error occurred immediately in $.ajax request.");
        console.log(result.responseText, result);
      },
      success: function (result) {
        if (result["status"]) {
          input.val("");
          form.addClass("hidden");
          span.removeClass("hidden");

          span.text(result["update_data"]["selling_price"]);
        } else {
          console.log(result["message"], result["error"]);
        }
      },
    });
  }
}

function changeSellingPrice(showInput, name) {
  name = name.replace(/%20/g, " ");
  let columnIndexRef = getColumnIndexes();
  let row = $("table#inventory tbody tr").filter(function () {
    return $(this).children().eq(columnIndexRef["name"]).text() == name;
  });
  let span = row.find(".sellingPriceSpan");
  let span_text = span.text();
  let form = row.find(".sellingPriceForm");
  let input = row.find(".sellingPriceInput");
  let button = row.find(".sellingPriceButton");

  if (!showInput){
      setTimeout(() => {
        console.log("!show")
    span.removeClass("hidden");
    form.addClass("hidden");
      }, 200);
  }

  if (showInput && !form.children(":focus").length){
    span.addClass("hidden");
    form.removeClass("hidden");
    input.val(span_text)
    input.focus();
    input.select();
  }
}

function bindUsefulJqueriesAfterLoadingDataIntoTable(){
  $('.buttonSubHolder').bind('mousewheel', function(e){
        
        let current_val = parseInt($(this).find("input").val())
        let max_buyable_quantity = Math.floor(parseInt($("#moneyStat").text()) / parseInt($(this).parents("tr").find(".restockPriceSpan").text()))

        if(e.originalEvent.wheelDelta/120 > 0) {
          current_val < max_buyable_quantity && $(this).find("input").val(current_val+1)
        }
        else{
          current_val > 1 && $(this).find("input").val(current_val-1)
        }
  });
}

function printDevDataHTML(popularity, max_buying_price){

  let show = <?php echo $_SESSION['show_dev_data']; ?>

  if (show){
    return "<p class='devdata1'>P"+popularity+"  M"+max_buying_price+"</p>"
  }else{
    return ""
  }
}

function restockFruit(formattedName) {
  name = formattedName.replace(/%20/g, " ");

  let columnIndexRef = getColumnIndexes();

  let row = $("table#inventory tbody tr").filter(function () {
    return $(this).children().eq(columnIndexRef["name"]).text() == name;
  });

  let requested_amount = parseInt(row.find(".amountInput_restock").val());
  let restock_price = parseInt(
    row.find(".restockPriceSpan").text()
  );
  let putative_cost = requested_amount * restock_price;
  let money = parseInt($("#moneyStat").text());

  setAmount(formattedName, "restock", "", requested_amount);

  if (putative_cost > money) {
    alert("Insufficient funds!");
  } else {
    $.ajax({
      type: "GET",
      url: "../api/fruit/restock.php",
      dataType: "json",
      data: {
        name: name,
        table_name: "<?php echo $inv_table_name; ?>",
        increment: requested_amount,
      },
      error: function (result) {
        console.log("An error occurred immediately in $.ajax request.");
        console.log(result.responseText, result);
      },
      success: function (result) {
        day_costs += putative_cost
        if (result["status"]) {
          let fruit = result["data"][0];

          let el = $("table#inventory tr td").filter(function () {
            return $(this).text() == fruit["name"];
          });

          el.parent("tr").find(".quantity").text(fruit["quantity"]);

          let maxPossibleToBuy = Math.floor(
            (money - putative_cost) / restock_price
          );

          if (requested_amount > maxPossibleToBuy) {
            let reset_value = maxPossibleToBuy || 1;

            el.parent("tr").find(".amountInput_restock").val(reset_value);
            seed_data.filter((item) => item["name"] == name)[0][
              "restock_amount"
            ] = reset_value;
          }
          // ***It was successful transaction. So we must send off the db to change money stat now.
          updateGamesTable(putative_cost, "decrement", null);
          
        } else {
          console.log(result["message"], result["error"]);
        }
      },
    });
  }
}

function calculateSales() {
  let incipient_sales = {};
  let num_rows = $("table#inventory tbody tr").filter(function () {
    return this;
  }).length;

  for (let i = 0; i < num_rows; i++) {
    let row = $("table#inventory tbody tr:eq(" + i + ")");
    let name = row.children().eq(columnIndexRef.name).text();
    let quantity = parseInt(row.find(".quantity").text());
    let selling_price = parseInt(
      row.children().eq(columnIndexRef["selling price"]).text()
    );

    let max_prices = seed_data.filter((item) => item.name == name)[0]
      .max_prices;
    let popularity_factors = seed_data.filter((item) => item.name == name)[0]
      .popularity_factors;
    let { popularity, max_buying_price, restock_price } = getSalesSubstrates(
      popularity_factors,
      max_prices,
      trend_calculates
    );

    let price_disparity =
      ((max_buying_price - selling_price) / max_buying_price) * 100;

    let sales_percentage = (popularity + price_disparity * 4) / 5 / 100;

    let sales_quantity = Math.ceil(sales_percentage * quantity);

    if (sales_quantity < 0) {
      sales_quantity = 0;
    } else if (sales_quantity > quantity) {
      sales_quantity = quantity;
    }

    let profit = sales_quantity * selling_price;

    incipient_sales[name] = { sales_quantity, profit };
  }
  return incipient_sales;
}

function printSingle(name) {
  name = name.replace(/%20/g, " ");
  $.ajax({
    type: "GET",
    url: "../api/fruit/read_single.php",
    dataType: "json",
    data: {
      table_name: "<?php echo $inv_table_name; ?>",
      identifying_column: "name",
      identifying_data: name,
      acronym: "s",
      get_full: false,
    },
    error: function (result) {
      console.log("An error occurred immediately in $.ajax request.", result);
      console.log(result.responseText);
    },
    success: function (result) {
      if (result["status"]) {
        console.log(result);
      } else {
        console.log(result["message"], result["error"]);
      }
    },
  });
}

function checkSession() {
  console.log(">>>Old session is:");
  console.log(`<?php print_r($_SESSION); ?>`);
}

function checkTCs() {
  console.log(">>>TC proxy:", trend_calculates);
  console.log(">>>Seed data proxy:", seed_data);
}

function getSalesSubstrates(popularity_factors, max_prices, trend_calculates) {
  let factor1 = getPopularityFactor(popularity_factors, 0, trend_calculates);
  let factor2 = getPopularityFactor(popularity_factors, 1, trend_calculates);
  let popularity = Math.ceil((factor1 * 3 + factor2) / 4);

  let popularity_word =
    popularity > 67 ? "High" : popularity < 33 ? "Low" : "Medium";
  let max_buying_price = max_prices[popularity_word];
  let restock_price = Math.ceil(0.8 * max_buying_price);

  return { popularity, max_buying_price, restock_price };
}

function getColumnIndexes() {
  function getIndex(match) {
    return $("table#inventory thead tr th")
      .filter(function () {
        return $(this).text().toLowerCase() == match;
      })
      .index();
  }

  let columnIndexRef = {};

  let labels = ["quantity", "selling price", "name", "restock price"];

  labels.forEach((label) => {
    columnIndexRef[label] = getIndex(label);
  });

  return columnIndexRef;
}

function updateGameStats(new_money_stat, new_days_stat, new_trend_calculates) {
  let el = $("*").filter(function () {
    return $(this).is("#moneyStat");
  });
  el.text(new_money_stat);

  if (new_days_stat) {
    el = $("*").filter(function () {
      return $(this).is("#daysStat");
    });
    el.text(new_days_stat);
  }

  verifyBuyButtons()

  if (new_trend_calculates) {
    trend_calculates = new_trend_calculates;
    updateSalesSubstratesInDisplayedTable();
  }
}

function updateSalesSubstratesInDisplayedTable() {
  let num_rows = $("table#inventory tbody tr").filter(function () {
    return this;
  }).length;
  let columnIndexRef = getColumnIndexes();

  for (let i = 0; i < num_rows; i++) {
    let name = $("table#inventory tbody tr")
      .eq(i)
      .children()
      .eq(columnIndexRef.name)
      .text();
    let formattedName = name;

    let max_prices = seed_data.filter((item) => item.name == formattedName)[0]
      .max_prices;
    let popularity_factors = seed_data.filter(
      (item) => item.name == formattedName
    )[0].popularity_factors;
    let { popularity, max_buying_price, restock_price } = getSalesSubstrates(
      popularity_factors,
      max_prices,
      trend_calculates
    );

    $("table#inventory tbody tr")
      .eq(i)
      .find(".devdata1")
      .text("P" + popularity + "  M" + max_buying_price);

    $("table#inventory tbody tr")
      .eq(i)
      .find(".popularityCircleSpan")
      .text(getPopularityColor(popularity).text)
      .css({"background-color": getPopularityColor(popularity).color})

      function getPopularityColor(pop){
        if (pop < 20){
          return {text: "⇊", color: "red"}
        } else if (pop < 40){
          return {text: "↓", color: "orange"}
        }  else if (pop < 60){
          return {text: "·", color: "yellow"}
        }  else if (pop < 80){
          return {text: "↑", color: "greenyellow"}
        }  else if (pop >= 80){
          return {text: "⇈", color: "cyan"}
        } 
      }

    $("table#inventory tbody tr")
      .eq(i)
      .find(".restockPriceSpan")
      .text(restock_price);
  }
}

function getPopularityFactor(pop_factor_names, i, trend_calculates) {
  let pop_keys = Object.keys(pop_factor_names);
  return pop_factor_names[pop_keys[i]]
    ? trend_calculates[pop_keys[i]]
    : 101 - trend_calculates[pop_keys[i]];
}

function throwFruit(formattedName) {
  name = formattedName.replace(/%20/g, " ");

  let columnIndexRef = getColumnIndexes();

  let row = $("table#inventory tbody tr").filter(function () {
    return $(this).children().eq(columnIndexRef["name"]).text() == name;
  });

  let throw_amount = parseInt(row.find(".amountInput_throw").val());
  let quantity = parseInt(row.find(".quantity").text());

  if (!quantity) {
    return;
  }

  setAmount(formattedName, "throw", "", throw_amount);

  if (throw_amount <= quantity) {
    if (
      throw_amount > 0.2 * quantity &&
      !confirm("Chuck " + throw_amount + " " + name + " into the street?")
    ) {
      return;
    }

    $.ajax({
      type: "GET",
      url: "../api/fruit/restock.php",
      dataType: "json",
      data: {
        name: name,
        table_name: "<?php echo $inv_table_name; ?>",
        increment: "-" + throw_amount.toString(),
      },
      error: function (result) {
        console.log("An error occurred immediately in $.ajax request.");
        console.log(result.responseText, result);
      },
      success: function (result) {
        if (result["status"]) {
          let fruit = result["data"][0];

          let el = $("table#inventory tr td").filter(function () {
            return $(this).text() == fruit["name"];
          });

          el.parent("tr").find(".quantity").text(fruit["quantity"]);

          if (throw_amount > parseInt(fruit["quantity"])) {
            let reset_value = parseInt(fruit["quantity"]) || 1;
            el.parent("tr").find(".amountInput_throw").val(reset_value);
            seed_data.filter((item) => item["name"] == name)[0][
              "restock_amount"
            ] = reset_value;
          }
        } else {
          console.log(result["message"], result["error"]);
        }
      },
    });
  }
}
</script>