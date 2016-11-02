function product_list_add_cart(itemCode) {
  alert ("Added " + itemCode + "to cart!");
} 
function update(){
  $('#updateButton').fadeOut(350, function() {
  var submit = document.getElementById("updateButton");
  submit.value = "Update";
  var reload = document.getElementById("formUpdate");
  reload.action ="/product-store/update-cart";
  $('#updateButton').fadeIn(350);
});; 
}

if ($(".empty_image")[0]){
  $(".cat_image").hide();
} else {
  $(".title_only").hide();
}

$(document).ready(function(){

  // SmartMenus jQuery init
  // $('#productMenu').smartmenus({
  //   mainMenuSubOffsetX: -1,
  //   subMenusSubOffsetX: 10,
  //   subMenusSubOffsetY: 0
  // });
  $( "span.sub-arrow" ).html( "" );


  // Instantiate EasyZoom plugin
  var $easyzoom = $('.easyzoom').easyZoom();

  // Get the instance API
  var api = $easyzoom.data('easyZoom');
  
  //   $('form[name="checkoutCart"]').submit(function(e) {
  //   e.preventDefault();
  //   $.ajax({
  //     method: "POST",
  //     url: "/product-store/complete-checkout",
  //     data: $('form[name="checkoutCart"]').serialize(),
  //     success: function(data) {
  //       if (data["status"] == 0) {
  //         $('.help-inline').html('');
  //         // $('.help-inline').parent().parent().removeClass('error');
  //         errors = data['errors'];
  //         for (var key in errors)
  //         {
  //           $('#'+key+'-error').html(errors[key]);
  //           // $('#'+key+'-error').parent().addClass('has-error');
  //         }
  //       }
  //       else if (data["status"] == 1) {
  //         window.location ="/product-store/complete-checkout";
  //       }
  //     },
  //     error: function(data) {
  //       alert("Error: connection could not be completed.");
  //     }
  //   });
  // });

});    