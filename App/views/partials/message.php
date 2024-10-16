<?php

use Framework\Session;

?>

<?php $success_message = Session::get_flash_msg("success_message"); ?>
<?php if($success_message !== null) : ?>
    <div class="messages bg-green-100 p-3 my-3">
        <?= $success_message ?>
    </div>
<?php endif; ?>

<?php $error_message = Session::get_flash_msg("error_message"); ?>
<?php if($error_message !== null) : ?>
    <div class="messages bg-red-100 p-3 my-3">
        <?= $error_message ?>
    </div>
<?php endif; ?>