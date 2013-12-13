<div id="contsetting">
    
    <div class="my_settings">

       {if $ERROR_FIELD}
       <div id="initial_message_area" class="alert alert-dismissable" style="text-align:center;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
           <p id="msg-text" class="alert-danger">{$ERROR_FIELD}</p> 
       </div>
       {else}
       <div id="message_area" class="alert oculto alert-dismissable" style="text-align:center;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
           <p id="msg-text"> </p> 
       </div>
       {/if}

        <div class="row">
            <div class="col-md-9">
                <button class="btn btn-default btn-sm" type="button" name="new_contact" onclick='newContact();'> <span class="glyphicon glyphicon-user"></span> New contact</button>
                <button class="btn btn-default btn-sm" type="button" name="upload_contact" onclick=''> <span class="glyphicon glyphicon-upload"></span> Upload from CSV</button>
                <div class="btn-group">
                    <button class="btn btn-default btn-sm dropdown-toggle" type="button" name="download_contact" data-toggle="dropdown" onclick=''> <span class="glyphicon glyphicon-download"></span> Download <span class="caret"></span></button>
                    <ul class="dropdown-menu" role="menu">
                        <li><a href="#"><span class="glyphicon glyphicon-file"></span> CSV (Legacy)</a></li>
                        <li><a href="#"><span class="glyphicon glyphicon-file"></span> XML</a></li>
                        <li><a href="#"><span class="glyphicon glyphicon-file"></span> CSV (Nested)</a></li>
                    </ul>
                </div>
                <button class="btn btn-default btn-sm" type="button" name="filter_contact" > <span class="glyphicon glyphicon-filter"></span> Show filter</button>
            </div>
        </div>

        <div class="row" >
            <div class="col-md-6"><p> </p></div>
        </div>

            <div class="row table-responsive">
                <table class="table table-condensed table-hover">
                    <thead>
                        <tr class="danger">
                          <td name-label>Pictures</td>
                          <td name-label>Name</td>
                          <td name-label>Extension</td>
                          <td name-label>Email</td>
                          <td name-label>Call</td>
                          <td name-label>Transfer</td>
                          <td name-label>Type Contact</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                          <td><span class="glyphicon glyphicon-user"></span></td>
                          <td>120</td>
                          <td>120</td>
                          <td> </td>
                          <td><span class="glyphicon glyphicon-earphone"></span></td>
                          <td>Transfer</td>
                          <td></span></td>
                        </tr>
                        <tr>
                          <td><span class="glyphicon glyphicon-user"></span></td>
                          <td>200</td>
                          <td>200</td>
                          <td> </td>
                          <td><span class="glyphicon glyphicon-earphone"></span></td>
                          <td>Transfer</td>
                          <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        
        
    </div>
</div>
