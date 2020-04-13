<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
?>

<?php if ($arResult["ERROR_MESSAGE"]) { ?>
    <p class="error"><?=$arResult["ERROR_MESSAGE"]?></p>
<?php } ?>

<?php if ($arResult["UPDATED"]) { ?>
    <p class="success"><?=GetMessage("DESCRIPTIONS_UPDATED")?></p>
<?php } ?>

<form action="" method="post">
    <input type="submit" value="<?=GetMessage("UPDATE_DESCRIPTIONS")?>" />
</form>
