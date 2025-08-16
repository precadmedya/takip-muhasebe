<?php
function fmt_date_tr(?string $date): string
{
    if(!$date) return '';
    $dt = new DateTime($date);
    return $dt->format('d.m.Y');
}
function fmt_money($value, string $currency='TRY'): string
{
    $fmt = number_format((float)$value,2,',','.');
    return $fmt.' '.$currency;
}
?>
