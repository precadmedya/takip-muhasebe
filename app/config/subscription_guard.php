<?php
function checkSubscription(PDO $pdo, int $firmId): array {
    $stmt = $pdo->prepare("SELECT *, DATEDIFF(end_date, CURDATE()) as days_left FROM firm_subscriptions WHERE firm_id=? ORDER BY end_date DESC LIMIT 1");
    $stmt->execute([$firmId]);
    $sub = $stmt->fetch();
    if(!$sub) return ['status'=>'missing','days_left'=>0];
    $today = new DateTime();
    $end = new DateTime($sub['end_date']);
    $grace = (clone $end)->modify('+'.$sub['grace_days'].' day');
    if($today <= $end) {
        return ['status'=>'active','days_left'=>$sub['days_left']];
    } elseif($today <= $grace) {
        return ['status'=>'grace','days_left'=>$today->diff($grace)->days];
    }
    return ['status'=>'expired','days_left'=>0];
}
