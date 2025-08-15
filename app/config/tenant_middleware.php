<?php
if(session_status() === PHP_SESSION_NONE) session_start();
function requireFirm(int $firmId): void {
    if(($_SESSION['user']['role'] ?? '') === 'firma' && $_SESSION['user']['firm_id'] != $firmId) {
        die('Yetkisiz erişim');
    }
}
