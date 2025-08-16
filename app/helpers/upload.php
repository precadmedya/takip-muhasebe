<?php
function upload_file(string $inputName, string $destDir, array $allowMime, int $maxBytes): array {
    if(!isset($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return ['status'=>false,'error'=>'Dosya yüklenemedi','path'=>null];
    }
    $file = $_FILES[$inputName];
    if($file['size'] > $maxBytes) {
        return ['status'=>false,'error'=>'Dosya boyutu çok büyük','path'=>null];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if(!in_array($mime,$allowMime,true)) {
        return ['status'=>false,'error'=>'Geçersiz dosya türü','path'=>null];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mimeExt = [
        'text/plain'=>'txt',
        'text/csv'=>'csv',
        'application/vnd.ms-excel'=>'csv',
        'image/png'=>'png',
        'image/jpeg'=>'jpg',
        'image/gif'=>'gif'
    ];
    $allowedExt = array_filter(array_map(fn($m) => $mimeExt[$m] ?? null, $allowMime));
    if(in_array($ext,['php','phtml','phar'],true) || ($allowedExt && !in_array($ext,$allowedExt,true))) {
        return ['status'=>false,'error'=>'Geçersiz dosya uzantısı','path'=>null];
    }
    if(!is_dir($destDir)) {
        @mkdir($destDir,0775,true);
    }
    $name = bin2hex(random_bytes(8)).'.'.$ext;
    $path = rtrim($destDir,'/').'/'.$name;
    if(!move_uploaded_file($file['tmp_name'],$path)) {
        return ['status'=>false,'error'=>'Dosya taşınamadı','path'=>null];
    }
    $root = dirname(__DIR__,2);
    $relative = str_replace($root,'',$path);
    return ['status'=>true,'error'=>null,'path'=>$relative];
}
?>
