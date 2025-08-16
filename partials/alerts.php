<?php
if(!empty($_SESSION['flash'])) {
    foreach($_SESSION['flash'] as $type => $msg) {
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'.htmlspecialchars($msg).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    unset($_SESSION['flash']);
}
?>
