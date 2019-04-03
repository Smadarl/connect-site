<form method="post">
    <input type="hidden" name="hid1" value="val1" />
    <input type="text" name="txt1" />
    <input type="submit" value="Submit" />
</form>
<?php
echo '<pre>';
var_dump($_REQUEST);
var_dump($_POST);
echo '</pre>';
phpinfo();

