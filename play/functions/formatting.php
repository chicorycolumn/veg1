<script>

function getSeasonDescription(days){
  if (days <= 91) {
    return "Spring"
  } else if (91 * 1 < days && days <= 91 * 2) {
    return "Summer"
  } else if (91 * 2 < days && days <= 91 * 3) {
    return "Autumn"
  } else {
    return "Winter"
  }
}

function toggleTooltips() {
  let toggle = $("#tooltipToggle");

  if (toggle.hasClass("halfOpacity")) {
    toggle.removeClass("halfOpacity");
    $("th").each(function () {
      if ($(this).children("span").length) {
        let text = $(this)
          .contents()
          .filter(function () {
            return this.nodeType == 3;
          })
          .text();

        $(this)
          .contents()
          .first()
          .replaceWith(text + " ⓘ");

        $(this).addClass("thHover");
      }
    });

    $(".popularityCircleSpan").addClass("popularityCircleSpanHover");
    $(".trendsChartTooltipHolder").removeClass("hidden")
    $(".trendsChartTooltipSymbol").addClass("trendsChartTooltipSymbolHover")
  } else {
    toggle.addClass("halfOpacity");
    $("th").each(function () {
      if ($(this).children("span").length) {
        let text = $(this)
          .contents()
          .filter(function () {
            return this.nodeType == 3;
          })
          .text();

        $(this).contents().first().replaceWith(text.replace(" ⓘ", ""));
        $(this).removeClass("thHover");
      }
    });
    $(".popularityCircleSpan").removeClass("popularityCircleSpanHover");
    $(".trendsChartTooltipHolder").addClass("hidden")
    $(".trendsChartTooltipSymbol").removeClass("trendsChartTooltipSymbolHover")
  }
}

function basicPageFunctions() {
  setZoom();
  adjustNavbarGameLink();
}

function adjustNavbarGameLink() {
  let check = parseInt('<?php echo check_gid(); ?>')

  if (!!check) {
    $("#navbarImagePlay").attr("src", "../images/cherry_sized_shadow2.png");
    $("#navbarImagePlay").attr("alt", "A cherry");
    $("#navbarLinkPlay").off("click", "startNewGame");
    $("#navbarLinkPlay").bind("click", loadPrevious);
  } else {
    $("#navbarImagePlay").attr("src", "../images/banana_sized_shadow2.png");
    $("#navbarImagePlay").attr("alt", "A banana");
    $("#navbarLinkPlay").off("click", "loadPrevious");
    $("#navbarLinkPlay").bind("click", startNewGame);
  }
}

function getPopularityFactor(pop_factor_names, i, trend_calculates) {
  let pop_keys = Object.keys(pop_factor_names);
  return pop_factor_names[pop_keys[i]]
    ? trend_calculates[pop_keys[i]]
    : 101 - trend_calculates[pop_keys[i]];
}

function bindUsefulJqueriesAfterLoadingDataIntoTable() {
  $(".buttonTD").bind("mousewheel", function (e) {
    let delta = e.originalEvent.wheelDelta;

    let scrollingOverTheTop = delta > 0 && this.scrollTop == 0;
    let scrollingOverTheBottom =
      delta < 0 && this.scrollTop >= this.scrollHeight - this.offsetHeight;
    if (scrollingOverTheBottom || scrollingOverTheTop) {
      e.preventDefault();
      e.stopPropagation();
    }

    let current_val = digitGrouping($(this).find("input").val(), true);
    let max_buyable_quantity = Math.floor(
      digitGrouping($("#moneyStat").text(), true) /
        digitGrouping(
          $(this).parents("tr").find(".restockPriceData").text(),
          true
        )
    );

    if (delta / 120 > 0) {
      current_val < max_buyable_quantity &&
        $(this)
          .find("input")
          .val(digitGrouping(current_val + 1));
    } else {
      current_val > 1 &&
        $(this)
          .find("input")
          .val(digitGrouping(current_val - 1));
    }
  });
}

function dayGrouping(days, years, ungroup) {
  days = parseInt(days);

  if (ungroup) {
    return parseInt(years) * 365 + days;
  }

  years = 0;

  while (days >= 365) {
    days -= 365;
    years++;
  }

  return { days, years };
}

function digitGrouping(num, ungroup) {
  if (num == "") {
    return num;
  }

  if (ungroup) {
    return parseInt(num.replace(/\s/g, ""));
  }

  num = num.toString();
  if (num.length <= 3) {
    return num;
  } else {
    return digitGrouping(num.slice(0, num.length - 3)) + " " + num.slice(-3);
  }
}

function inputDigitGrouping(e) {
  e.target.value = digitGrouping(digitGrouping(e.target.value, true));
}

function allButtonsDisabled(toggle) {
  $(document).ready(function () {
    if (toggle) {
      $("button").attr("disabled", true);
    } else {
      $("button").removeAttr("disabled");
    }
  });
}

function parseIntObjectValues(obj) {
  for (key in obj) {
    obj[key] = parseInt(obj[key]);
  }
  return obj;
}

function verifyBuyButtons() {

  if(!$(".dialogHolder").hasClass("hidden")){
    allButtonsDisabled(true)
    return
  }

  $(".buyButton").each(function () {
    let row = $(this).parents("tr");
    let name = row.find(".nameData").text();
    let restockPrice = digitGrouping(
      row.find(".restockPriceData").text(),
      true
    );
    let restockQuantity = digitGrouping(
      row.find(".amountInput_restock").val(),
      true
    );
    let maxBuyableQuantity = Math.floor(
      digitGrouping($("#moneyStat").text(), true) / restockPrice
    );

    if (restockQuantity > maxBuyableQuantity || !restockQuantity) {
      $(this).attr("disabled", true);
      setTimeout(() => {
        $(this).addClass("buyButtonHover");
      }, 1000);
    } else {
      $(this).removeAttr("disabled");
      $(this).removeClass("buyButtonHover");
    }

  });
}

function setZoom() {
  let scale = 0.75;
  document.body.style.zoom = scale;
  document.body.style.webkitTransform = scale;
  document.body.style.msTransform = scale;
  document.body.style.transform = scale;
}

function updateTimestamp() {
  $.ajax({
    type: "POST",
    url: "../api/fruit/update_timestamp.php",
    dataType: "json",
    data: {
      time: Date.now(),
    },
    error: function (result) {
      console.log(
        "An error when updating timestamp.",
        result,
        result.responseText
      );
    },
    success: function (result) {
      if (!result["status"]) {
        console.log("Did not manage to update timestamp.");
      }
    },
  });
}

</script>