<?php
function paginate(int $total, int $page, int $perPage): array {
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    $offset = ($page - 1) * $perPage;
    return ['total'=>$total,'pages'=>$pages,'page'=>$page,'per_page'=>$perPage,'offset'=>$offset];
}
function pagination_links(array $pagination, array $query): string {
    if($pagination['pages'] <= 1) return '';
    $html = '<nav><ul class="pagination">';
    for($i=1;$i<=$pagination['pages'];$i++) {
        $query['page'] = $i;
        $url = '?' . http_build_query($query);
        $active = $i == $pagination['page'] ? ' active' : '';
        $html .= '<li class="page-item'.$active.'"><a class="page-link" href="'.$url.'">'.$i.'</a></li>';
    }
    $html .= '</ul></nav>';
    return $html;
}
?>
