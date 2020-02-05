<?php

Authentication::authenticate();
$HtmlPage = new HtmlPage();
$HtmlPage->PrintHeaderExt();

$projectId = $_GET["pid"];
$assetId = $_GET["id"];
global $conn;
global $module;
/**
* @var $module \ExternalModules\AbstractExternalModule
*/
$salt = $module->getProjectSetting("salt");

?>
<h1 class="h1">Link to the REDCap webcalendar.</h1>
<p>

    <?php
    if ($salt === null || trim($salt) == "")
    {
    ?>
<p class="alert alert-warning">No salt was configured. Make sure you have a secure salt configured in your module configuration.</p>
<?php
exit(0);
}

//$postUrl = $this->;// $module->getUrl("showDoc.php", true, true);//TRIED THIS FOR 4 HOURS AND GAVE UP. _SOMETHING_ WRONG WITH THE API PARAMETERS.
    $url = "webcal://$_SERVER[HTTP_HOST]".APP_PATH_WEBROOT_PARENT."/webcalendar/P".$projectId."ID".$salt.".ics";

?>
Copy this link to your outlook:
<div class="card text-white bg-info mb-3">
    <div class="card-header">
        Public URL
    </div>
    <div class="card-body">
        <input class="form-control" type="text"  id="myInput" value="<?php echo $url; ?>">

        <!-- The button used to copy the text -->
        <button class="form-control" onclick="myFunction()">Copy to Clipboard</button>
    </div>
</div>

<BR/>

<p class="alert alert-warning">Please note that anyone with this link can access this calendar.</p>

<BR/>


<script>
    function myFunction() {
        /* Get the text field */
        var copyText = document.getElementById("myInput");

        /* Select the text field */
        copyText.select();
        copyText.setSelectionRange(0, 99999); /*For mobile devices*/

        /* Copy the text inside the text field */
        document.execCommand("copy");

        /* Alert the copied text */
    }

</script>