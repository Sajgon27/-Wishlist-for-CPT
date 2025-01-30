

jQuery(document).ready(function ($) {
    console.log('g');
  function updateWishlistCount() {
    $.ajax({
      type: "POST",
      url: wishlist_ajax.ajax_url,
      data: { action: "wishlist_get_count" },
      success: function (response) {
        if (response.success) {
          console.log("eo");
          $("#wishlist-count").text(response.data.count);
        }
      },
    });
  }

  $(".wishlist-button").on("click", function () {
    var button = $(this);
    var post_id = button.data("postid");

    $.ajax({
      type: "POST",
      url: wishlist_ajax.ajax_url,
      data: {
        action: "wishlist_toggle",
        post_id: post_id,
        nonce: wishlist_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          button.toggleClass("added");
          if ($(button).hasClass("added")) {
            $(button).text("Usuń z listy -");
          } else {
            $(button).text("Dodaj do listy +");
          }
          updateWishlistCount();
        }
      },
    });
  });

  updateWishlistCount();

  jQuery(".quantity-price input").each(function () {
    let element = jQuery(this);
    let price = parseInt(element.data("price"));
    let postid = element.data("postid");
    let priceField = jQuery(`span[data-postid='${postid}']`);
    let maxVal = parseInt(element.attr("max"));

    element.on("change", function () {
      if (element.val() > maxVal) {
        element.val(maxVal);
      }
      let sum = element.val() * price;
      priceField.html(sum + " złotych");
    });
  });

  jQuery("form[name=wishlist-form]").on("submit", function () {
    let dataToSend = {};

    jQuery(".wishlist-items li").each(function () {
      let data = [];

      let title = jQuery(this).find("h3").text();
      let price = jQuery(this).find("a div p").text();
      let quantity = jQuery(this).find(".quantity-price input").val();

      data.push(price);
      if (quantity) {
        data.push(quantity);
      }

      dataToSend[title] = data;
    });

    let hiddenField = jQuery("#form-field-field_fb72342");
    if (hiddenField.length) {
      hiddenField.val(JSON.stringify(dataToSend));
    }
  });
});
