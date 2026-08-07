<?php
declare(strict_types=1);
require dirname(__DIR__).'/lib.php';

$in=$_SERVER['REQUEST_METHOD']==='POST'?(json_decode((string)file_get_contents('php://input'),true)?:[]):$_GET;
$id=(int)($in['id']??0);$cid=(string)($in['conversation_id']??'');$token=(string)($in['token']??'');$type=(string)($in['type']??'impression');
if($id<1||!in_array($type,['impression','click'],true)||!hash_equals(kce_sign($id,$cid),$token))kce_json(['error'=>'Event tidak valid'],422);

try{
    $db=kce_db();
    $db->begin_transaction();
    $s=$db->prepare("SELECT s.advertiser_id,s.target_url,s.impression_unit_cost,s.click_unit_cost,c.id conversation_pk FROM kce_sponsored_content s LEFT JOIN kce_conversations c ON c.public_id=? WHERE s.id=? AND s.status='active' FOR UPDATE");
    $s->bind_param('si',$cid,$id);$s->execute();$ad=$s->get_result()->fetch_assoc();$s->close();
    if(!$ad){$db->rollback();kce_json(['error'=>'Materi tidak aktif'],404);}

    $campaignCost=$type==='click'?$ad['click_unit_cost']:$ad['impression_unit_cost'];
    $cost=$campaignCost!==null?(float)$campaignCost:(float)kce_setting($db,$type==='click'?'sponsored_click_cost':'impression_article_cost','0');
    $bucket=$type==='impression'?gmdate('YmdH'):bin2hex(random_bytes(8));
    $eventKey=hash('sha256',"$type|$id|$cid|$bucket");$ip=kce_ip_hash();$ua=mb_substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500);$cp=$ad['conversation_pk']?(int)$ad['conversation_pk']:null;

    // UNIQUE event_key membuat impression/click retry tidak ditagihkan dua kali.
    $s=$db->prepare('INSERT IGNORE INTO kce_ad_events(sponsored_content_id,conversation_id,event_type,charge_amount,event_key,ip_hash,user_agent) VALUES(?,?,?,?,?,?,?)');
    $s->bind_param('iisdsss',$id,$cp,$type,$cost,$eventKey,$ip,$ua);$s->execute();$inserted=$s->affected_rows===1;$eventId=(int)$db->insert_id;$s->close();
    if(!$inserted){
        $db->commit();
        if($type==='click'){header('Location: '.$ad['target_url'],true,302);exit;}
        kce_json(['ok'=>true,'duplicate'=>true]);
    }

    $advertiserId=(int)$ad['advertiser_id'];
    $s=$db->prepare('INSERT IGNORE INTO kce_advertiser_wallets(advertiser_id,balance) VALUES(?,0)');$s->bind_param('i',$advertiserId);$s->execute();$s->close();
    $s=$db->prepare('SELECT balance FROM kce_advertiser_wallets WHERE advertiser_id=? FOR UPDATE');$s->bind_param('i',$advertiserId);$s->execute();$balance=(float)$s->get_result()->fetch_column();$s->close();
    if($cost>0&&$balance<$cost){
        $db->rollback();
        $s=$db->prepare("UPDATE kce_sponsored_content SET status='paused' WHERE id=? AND status='active'");$s->bind_param('i',$id);$s->execute();$s->close();
        kce_json(['error'=>'Saldo advertiser tidak mencukupi; campaign dipause.'],402);
    }

    $after=$balance-$cost;
    $s=$db->prepare('UPDATE kce_advertiser_wallets SET balance=? WHERE advertiser_id=?');$s->bind_param('di',$after,$advertiserId);$s->execute();$s->close();
    $transactionType=$type.'_charge';$amount=-$cost;$description='Biaya '.$type.' KCE campaign #'.$id;
    $s=$db->prepare('INSERT INTO kce_wallet_transactions(advertiser_id,sponsored_content_id,ad_event_id,transaction_type,amount,balance_before,balance_after,description) VALUES(?,?,?,?,?,?,?,?)');
    $s->bind_param('iiisddds',$advertiserId,$id,$eventId,$transactionType,$amount,$balance,$after,$description);$s->execute();$s->close();
    $db->commit();
    if($type==='click'){header('Location: '.$ad['target_url'],true,302);exit;}
    kce_json(['ok'=>true]);
}catch(Throwable $x){
    if(isset($db))try{$db->rollback();}catch(Throwable $ignored){}
    error_log('KCE event: '.$x->getMessage());kce_json(['error'=>'Event gagal dicatat'],500);
}
