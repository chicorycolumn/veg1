<?php

$_SESSION['show_dev_data'] = 0;
$content = "";

if ($show_dev_data) {
  $_SESSION['show_dev_data'] = 1;
  $content =
    '<h1>' .
    $gid .
    '</h1><button onClick="printSingle(null)">Check Game Data</button>';
}

$content .=
  '
  <div class="dialogHolder hidden">
    <img class="scroll1" src=".././images/scroll1.png">
    <div class="dialogBox">
      <div class="dialogBoxInner">
        <div class="dialogBoxInnerInner">
          <p class=dialogBoxText></p>
        </div>
      </div>
      <p class="dialogBoxButton" onClick=advance()>☞Very well</p>
    </div>
  </div>

  <div class="mainDiv mainDivStats">
    ' .
  $mainStats .
  '
  </div>

  <div class="holderForHorizontalMainDivs">

    <div class="mainDiv mainDivGraphs">
    ' .
  $mainGraphs .
  '
    </div>

    <div class="mainDiv mainDivBulletin">
      ' .
  $mainBulletin .
  '
    </div>

  </div>
    
  <div class="mainDiv mainDivTable">
    ' .
  $invTable .
  '
  </div>
  ';

?>