$(document).ready(function(){ 
$('.checkall').click(function () {
     $(".neo-table-data-row").find(':checkbox').attr('checked', this.checked);
});

 setFaxMsg();
    
})



function setFaxMsg(){
var arrAction        = new Array();
    arrAction["action"]  = "setFaxMsg";
    arrAction["menu"]    = "faxlist";
    arrAction["rawmode"] = "yes";
  //  arrAction["pdf_file"] = pdf_file;
    
    request("index.php",arrAction,true,
            function(arrData,statusResponse,error)
            {
               var estado;
               $(".neo-table-data-row a.doc").each(function(){
                
                var doc = $(this).html(); 
                var iddoc = doc.replace(".pdf",''); 
                var pdf_file = doc.replace("doc",'');
                var pdf_file = parseInt(pdf_file.replace(".pdf",''));
	        if ( typeof  arrData["state"][pdf_file]!== "undefined" && arrData["state"][pdf_file]){ 
                 if(pdf_file>0){
                   if(arrData["state"][pdf_file][2]=='F')
                     estado = "<div style='color:red'>Failed </div>";
                   else
                     estado = "<div style='color:green'>Ok</div>";
                   
                 $("#doc"+arrData["state"][pdf_file][0]).html(estado);
                 }
               }

               });
               
               return true;

            });

}
