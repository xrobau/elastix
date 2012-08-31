function radio(id_radio){
    var alt=$("#content_"+id_radio).children("table").height();
    var alt_msg_error=$("#message_error").height();
    if(alt_msg_error==null)
        alt_msg_error=0;
    else
        alt_msg_error=alt_msg_error-5;
    var alt_tab=alt+37+alt_msg_error;
    var alt_div=alt_tab+42+alt_msg_error;
    $(".tabs").css({'height':alt_tab+"px"});
    $(".neo-module-content").css({'height':alt_div+"px"});
    $(".content").css({"z-index":"0"});
    $("div.tab > .content > *").css({"opacity":"0", "-moz-transform": "translateX(-100%)","-webkit-transform":"translateX(-100%)","-o-transform":"translateX(-100%)","-moz-transition":"all 0.6s ease","-webkit-transition":"all 0.6s ease","-o-transition":"all 0.6s ease"});
    $("#content_"+id_radio).css({"z-index":"1"});
    $("#content_"+id_radio+" > *").css({"opacity":"1", "-moz-transform":"translateX(0)", "-webkit-transform":"translateX(0)", "-o-transform":"translateX(0)", "-ms-transform":"translateX(0)"});
    //div de las tabs
    var d_label=$("#"+id_radio).parent();
    $(".neo-table-header-row-filter").css("background","none");
    $(".neo-table-header-row-filter").css("color","BLACK");
    d_label.css("background","-moz-linear-gradient(center top , #777777, #999999)");
    d_label.css("background","-webkit-gradient(linear,0% 40%,0% 70%,from(#777),to(#999))");
    d_label.css("background","linear-gradient(center top , #777777, #999999)");
    d_label.css("border-color"," #888888"); 
    d_label.css("color"," #FFFFFF"); 
}

