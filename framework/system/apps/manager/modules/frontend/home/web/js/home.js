$(document).ready(function(){
 table = $('#table');
 bodymail= $('#bodymail');
 newmail= $('#createmail');
 checkmail= $("input[name=checkmail]"); 
 row=$('.row'); 
 
 checkmail.on("click", function(){
 mailnum=this.value; 
 if(this.checked){
    $('#1'+mailnum).css("background-color","rgb(200,200,200)");
    $('#0'+mailnum).css("background-color","rgb(200,200,200)");
  }else { 
    $('#1'+mailnum).css("background-color","rgb(255, 255, 255)");
    $('#0'+mailnum).css("background-color","rgb(229, 229, 229)");
  }
  });
});

function view_body(UID){
 
 table.hide(10);
 var arrAction = new Array();
 arrAction["menu"]="user_home";
 arrAction["action"]="view_bodymail";
 arrAction["idMail"]=UID;
 arrAction["rawmode"]="yes";
 request("index.php", arrAction, false,
        function(arrData,statusResponse,error){
            if(error!=""){
                alert(error);
            }else{
               bodymail.append("<p>"+arrData+"</p>")
                }     
    });
 bodymail.show(10);
 $('#0'+UID).attr('id','1'+UID);
 $('#1'+UID).css("background-color","rgb(255, 255, 255)");
}

function create_showInbox(){
table.show(10);
bodymail.hide(10);
newmail.hide(10);

}

 


