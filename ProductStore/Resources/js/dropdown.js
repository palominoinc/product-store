 
    $(document).ready(function() {
    $(".js-example-basic-single").select2(
    {
            placeholder: "Select Address",
            allowClear: true
        });
});

  function PopulateShipping() {
    var dropdown = document.getElementById("shipid");
    var selectedoption = $('#shipid option:selected').text().split(",");
    var field = document.getElementById("fullship"); 
    field.value = ""; 
    
    if(selectedoption[0] != null) {
      field.value += selectedoption[0] + "\n ";

      if(selectedoption[1] !=null){
        field.value += selectedoption[1] + "\n "; 
      }
      if(selectedoption[2] !=null){
        field.value += selectedoption[2] + "\n ";
      }
      if(selectedoption[3] !=null) {
        field.value += selectedoption[3] + "\n ";
      }

      if(selectedoption[4] !=null) {
        field.value += selectedoption[4] + "\n ";
      }
      if(selectedoption[5] !=null) {
        field.value += selectedoption[5];
      }
    }
     
    // var address = str[0];
    // var postal = str[1];
    // var city = str[3];
    // var field = document.getElementById("shippingaddress1");
    // var field2= document.getElementById("shippingpostal");
    // var field3= document.getElementById("shippingtown");
    // field.value = address;
    // field2.value = text[1];
    // field3.value = postal; 
    // var dropdown = document.getElementById("nameid");
    // var field = document.getElementById("shippingaddress1");
    // field.value = dropdown.value;
}
 
  function PopulateBilling() {
    
    var selectedoption = $('#billid option:selected').text().split(",");
    var field = document.getElementById("billaddress"); 
    field.value = ""; 
    
    if(selectedoption[0] != null) {
    field.value += " "+ selectedoption[0] + "\n ";
   
      if(selectedoption[1] !=null){
        field.value += selectedoption[1] + "\n "; 
      }
      if(selectedoption[2] !=null){
        field.value += selectedoption[2] + "\n ";
      }
      if(selectedoption[3] !=null) {
        field.value += selectedoption[3] + "\n ";
      }
      
      if(selectedoption[4] !=null) {
        field.value += selectedoption[4] + "\n ";
      }
      if(selectedoption[5] !=null) {
        field.value += selectedoption[5];
      }
    }
    
    // var dropdown = document.getElementById("billid");
    // var str= dropdown.value.split(",");
    // var address = str[0];
    // var postal = str[1];
    // var city = str[2];
    // var field = document.getElementById("billingaddress1");
    // var field2= document.getElementById("billingpostal");
    // var field3= document.getElementById("billingtown");
    // field.value = address;
    // field2.value = city;
    // field3.value = postal; 
    
} 

function newField(){
  
var ddl = document.getElementById("invoiceemails");
    var selectedValue = ddl.options[ddl.selectedIndex].value;


    if (selectedValue == "Other")
    {   document.getElementById("inemail").style.display = "block";
    }
    else
    {
       document.getElementById("inemail").style.display = "none";
       document.getElementById("inemail").value = selectedValue;
    }

}
