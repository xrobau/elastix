
<link rel="stylesheet"         href="modules/{$module_name}/themes/faq.css" type="text/css" />
<script type="text/javascript" src ="/modules/{$module_name}/themes/jquery.faq.js"></script>

<link   rel ="stylesheet"      href="modules/{$module_name}/themes/style.css" />
<script type="text/javascript" src ="/modules/{$module_name}/themes/javascript.js"></script>

<div id="contentWrap">
    <div id="content">

    <div id="tool"> 
        <input type="button" value="Reload" onclick="loadSizeArea()"/>
        <span id="timewaiting">Half waiting time Queue: </span>
        <span id="allqueue">Calls in Queue: {$total_queues}</span>
    </div>

    <ul id="sortable-list1">
    <div id="area_1" class="left_side boxDrop">
        <li id="headExtension">
        {$descripArea1} 
        <table border ='0' cellspacing="0" cellpadding="0">
            <tr id="tableExtension">
            </tr>
        </table>
        </li>
    </div>

    <div id="area_6" class="left_side">
        <li id="headTrunks">
        {$descripArea6}
            <table border ='0' cellspacing="0" cellpadding="0">
                <tr id="tableTrunks">
                </tr>
            </table>
        </li>
    </div><!--End ContentTrunks -->

    <div id="area_7" class="left_side">
        <li id="headTrunksSIP">
        {$descripArea7}
            <table border ='0' cellspacing="0" cellpadding="0">
                <tr id="tableTrunksSIP">
                </tr>
            </table>
        </li>
    </div><!--End ContentTrunks -->    

    </ul>
    </div><!--End Content -->

    <dl id="faq">
        <ul id="sortable-list2" class="sortable">
            <li class="state1">
            <dt id="headArea1">{$descripArea2} -- {$lengthArea2} ext<div  style = 'float:right;'><span id="editArea2">[Edit Name]</span></div></dt>
            <dd id="area_2" class="right_side boxDrop">
                <table border ='0' cellspacing="0" cellpadding="0">
                    <tr id="tableArea1"><br />
                    </tr>
                </table>
            </dd>
            </li>
            <li class="state1">
            <dt id="headArea2">{$descripArea3} -- {$lengthArea3} ext<div  style = 'float:right;'><span id="editArea3">[Edit Name]</span></div></dt>
            <dd id="area_3" class="right_side boxDrop">
                <table border ='0' cellspacing="0" cellpadding="0">
                    <tr id="tableArea2"><br />
                    </tr>
                </table>
            </dd>
            </li> 
            <li class="state1">
            <dt id="headArea3">{$descripArea4} -- {$lengthArea4} ext<div  style = 'float:right;'><span id="editArea4">[Edit Name]</span></div></dt>
            <dd id="area_4" class="right_side boxDrop">
                <table border ='0' cellspacing="0" cellpadding="0">
                    <tr id="tableArea3"><br />
                    </tr>
                </table>
            </dd>
            </li>
            <li class="state1">
            <dt id="headConferences">{$descripArea8} </dt>
            <dd id="area_8" class="right_side" >
                <table border ='0' cellspacing="0" cellpadding="0">
                    <tr id="tableConferences"><br />
                    </tr>
                </table>
            </dd>
            </li>

            <li class="state1">
            <dt id="headParkinglots">{$descripArea9} </dt>
            <dd id="area_9" class="right_side">
                <table border ='0' cellspacing="0" cellpadding="0">
                    <tr id="tableParkinglots"><br />
                    </tr>
                </table>
            </dd>
            </li>

            <li class="state1">
            <dt id="headQueues">{$descripArea5} </dt>
            <dd id="area_5" class="right_side" >
                <table border ='0' cellspacing="0" cellpadding="0">
                    <tr id="tableQueues"><br />
                    </tr>
                </table>
            </dd>
            </li>
        </ul>
        <ul id="sortable-hidden" class="sortable">
            <li class="state2">
            </li>
        </ul>
    </dl>
    
</div> <!--End of the div contentWrap-->

<div id='layerCM'>
      <div class='layer_handle' id='closeCM'></div>
      <div id='layerCM_content'></div>
</div>

<input type="hidden" id="lengthA2" name="lengthA2" value="{$lengthArea2}"/>
<input type="hidden" id="lengthA3" name="lengthA3" value="{$lengthArea3}"/>
<input type="hidden" id="lengthA4" name="lengthA4" value="{$lengthArea4}"/>

<input type="hidden" id="nameArea1" name="nameArea1" value="{$nameA1}"/>
<input type="hidden" id="nameArea2" name="nameArea2" value="{$nameA2}"/>
<input type="hidden" id="nameArea3" name="nameArea3" value="{$nameA3}"/>
<input type="hidden" id="nameArea4" name="nameArea4" value="{$nameA4}"/>
<input type="hidden" id="nameArea5" name="nameArea5" value="{$nameA5}"/>
<input type="hidden" id="nameArea6" name="nameArea6" value="{$nameA6}"/>
<input type="hidden" id="nameArea7" name="nameArea7" value="{$nameA7}"/>
<input type="hidden" id="nameArea8" name="nameArea8" value="{$nameA8}"/>
<input type="hidden" id="nameArea9" name="nameArea9" value="{$nameA9}"/>

<input type="hidden" id="heightA1" name="heightA1" value="{$height1}"/>
<input type="hidden" id="heightA2" name="heightA2" value="{$height2}"/>
<input type="hidden" id="heightA3" name="heightA3" value="{$height3}"/>
<input type="hidden" id="heightA4" name="heightA4" value="{$height4}"/>
<input type="hidden" id="heightA5" name="heightA5" value="{$height5}"/>
<input type="hidden" id="heightA6" name="heightA6" value="{$height6}"/>
<input type="hidden" id="heightA7" name="heightA7" value="{$height7}"/>
<input type="hidden" id="heightA8" name="heightA8" value="{$height8}"/>
<input type="hidden" id="heightA9" name="heightA9" value="{$height9}"/>

<input type="hidden" id="widthA1" name="widthA1" value="{$width1}"/>
<input type="hidden" id="widthA2" name="widthA2" value="{$width2}"/>
<input type="hidden" id="widthA3" name="widthA3" value="{$width3}"/>
<input type="hidden" id="widthA4" name="widthA4" value="{$width4}"/>
<input type="hidden" id="widthA5" name="widthA5" value="{$width5}"/>
<input type="hidden" id="widthA6" name="widthA6" value="{$width6}"/>
<input type="hidden" id="widthA7" name="widthA7" value="{$width7}"/>
<input type="hidden" id="widthA8" name="widthA8" value="{$width8}"/>
<input type="hidden" id="widthA9" name="widthA9" value="{$width9}"/>