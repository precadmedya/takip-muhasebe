<?php
if(session_status() === PHP_SESSION_NONE) session_start();
function isYonetici(): bool {
    return ($_SESSION['user']['role'] ?? '') === 'yonetici';
}
function isFirma(): bool {
    return ($_SESSION['user']['role'] ?? '') === 'firma';
}
