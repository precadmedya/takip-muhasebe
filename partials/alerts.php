<?php
if(isset($GLOBALS['subscription_status']) && $GLOBALS['subscription_status']['status']==='grace') {
    echo '<div class="alert alert-warning text-center sticky-top" role="alert" aria-live="polite">Aboneliğiniz yenilenmek üzere; kritik işlemler devre dışıdır.</div>';
}
if(!empty($_SESSION['flash'])) {
    foreach($_SESSION['flash'] as $type => $msg) {
        echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'.htmlspecialchars($msg).'<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }
    unset($_SESSION['flash']);
}
?>
