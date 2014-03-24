<html>
    <head>
        <title>{$TAG_EVENTTYPE|escape:html}</title>
    </head>
    <body>
        <style>{literal}
            .title{
                background-color:#D1E6FA;
                color:#000000;
            }
            .tr{
                background-color:#F1F8FF;
            }
            .td1{
                font-weight: bold;
                color:#b9b2b2; 
                font-size: large;
                width:165px;
            }
            .footer{
                background-color:#EBF5FF;
                color:#b9b2b2;
                font-weight:bolder;
                font-size:12px;
            }{/literal}
        </style>
        <div>
            <table width='600px'>
                <tr class='title'><td colspan='2'><center><h1>{$TAG_EVENTTYPE|escape:"html"}</h1></center></td></tr>
                <tr class='tr'><td class='td1'>{$TAG_EVENT|escape:"html"}: </td><td>{$event->title|escape:"html"}.</td></tr>
                <tr class='tr'><td class='td1'>{$TAG_DATE_START|escape:"html"}: </td><td>{$event->timestamp_start|date_format:"%d %b %Y"}.</td></tr>
                <tr class='tr'><td class='td1'>{$TAG_DATE_END|escape:"html"}: </td><td>{$event->timestamp_end|date_format:"%d %b %Y"}.</td></tr>
                <tr class='tr'><td class='td1'>{$TAG_TIME_INTERVAL|escape:"html"}:</td><td>{$event->timestamp_start|date_format:"%H:%M:%S"} - {$event->timestamp_end|date_format:"%H:%M:%S"}.</td></tr>
                <tr class='tr'><td class='td1'>{$TAG_DESCRIPTION|escape:"html"}: </td><td>{$event->description|escape:"html"}.</td></tr>
                <tr class='tr'><td class='td1'>{$TAG_ORGANIZER|escape:"html"}: </td><td><span>{$USER_NAME|escape:"html"}.</span></td></tr>
                <tr class='footer'><td colspan='2'><center><span>{$MSG_FOOTER|escape:"html"}</span></center></td></tr>
            </table>
        </div>
    </body>
</html>
