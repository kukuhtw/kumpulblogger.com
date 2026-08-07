<?php
declare(strict_types=1);

function kce_env(): array {
    static $env;
    if ($env === null) {
        $file = dirname(__DIR__, 2) . '/.env';
        $fileEnv = is_readable($file) ? (parse_ini_file($file, false, INI_SCANNER_RAW) ?: []) : [];
        $processEnv = getenv();
        $env = array_merge($fileEnv, is_array($processEnv) ? $processEnv : []);
    }
    return $env;
}
function kce_db(): mysqli {
    static $db;
    if ($db instanceof mysqli) return $db;
    $e = kce_env();
    $db = new mysqli((string)$e['DB_HOST'], (string)$e['DB_USERNAME'], (string)$e['DB_PASSWORD'], (string)$e['DB_DATABASE']);
    if ($db->connect_errno) throw new RuntimeException('Database tidak tersedia.');
    $db->set_charset('utf8mb4');
    return $db;
}
function kce_json(array $data, int $status = 200): never {
    http_response_code($status); header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store'); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
}
function kce_verify_human(string $token): bool {
    $secret=(string)(kce_env()['RECAPTCHA_SECRET']??'');if($secret===''||$token==='')return false;
    $ch=curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>12,CURLOPT_POSTFIELDS=>http_build_query(['secret'=>$secret,'response'=>$token,'remoteip'=>$_SERVER['REMOTE_ADDR']??''])]);
    $raw=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);$r=json_decode((string)$raw,true);
    return $code===200&&!empty($r['success'])&&($r['action']??'')==='kce_chat'&&(float)($r['score']??0)>=0.5;
}
function kce_set_conversation_cookie(string $id): void {
    setcookie('kce_conversation',$id,['expires'=>time()+31536000,'path'=>'/kce','secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'),'httponly'=>true,'samesite'=>'Lax']);
}
function kce_public_url(?string $url): ?string {
    $url=trim((string)$url);if($url==='')return null;if(filter_var($url,FILTER_VALIDATE_URL))return $url;
    if(!str_starts_with($url,'/'))$url='/'.ltrim($url,'/');$env=kce_env();$configured=(string)($env['KCE_APP_URL']??'');$parts=parse_url($configured);
    if(!empty($parts['scheme'])&&!empty($parts['host'])){$origin=$parts['scheme'].'://'.$parts['host'].(isset($parts['port'])?':'.$parts['port']:'');return $origin.$url;}
    $domain=preg_replace('/[^A-Za-z0-9.:-]/','',(string)($env['DOMAIN_NAME']??'kumpulblogger.com'));return 'https://'.$domain.$url;
}
function kce_uuid(): string {
    $b=random_bytes(16); $b[6]=chr((ord($b[6])&15)|64); $b[8]=chr((ord($b[8])&63)|128);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($b),4));
}
function kce_ip_hash(): string { return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . (kce_env()['KCE_TRACKING_SECRET'] ?? 'kce')); }
function kce_http(string $url, string $key, array $payload, array $extra=[]): array {
    $ch=curl_init($url); $headers=array_merge(['Authorization: Bearer '.$key,'Content-Type: application/json'], $extra);
    curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>45,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_HTTPHEADER=>$headers,CURLOPT_POSTFIELDS=>json_encode($payload)]);
    $raw=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
    $out=json_decode((string)$raw,true);
    if ($code<200 || $code>=300 || !is_array($out)) throw new RuntimeException($out['error']['message'] ?? $err ?: 'Layanan AI gagal merespons.');
    return $out;
}
function kce_embedding(string $text, string $type='query'): array {
    $e=kce_env(); $key=(string)($e['NVIDIA_API_KEY'] ?? '');
    if ($key==='') throw new RuntimeException('NVIDIA_API_KEY belum dikonfigurasi.');
    $model=(string)($e['NVIDIA_EMBEDDING_MODEL'] ?? 'nvidia/nemotron-3-embed-1b');
    $r=kce_http('https://integrate.api.nvidia.com/v1/embeddings',$key,['input'=>[$text],'model'=>$model,'input_type'=>$type,'encoding_format'=>'float','truncate'=>'END']);
    $v=$r['data'][0]['embedding'] ?? [];
    if (!is_array($v) || count($v)!==2048) throw new RuntimeException('Embedding NVIDIA harus berdimensi 2048.');
    return array_map('floatval',$v);
}
function kce_cosine(array $a,array $b): float {
    if (count($a)!==2048 || count($b)!==2048) return -1.0;
    $dot=$aa=$bb=0.0; for($i=0;$i<2048;$i++){ $x=(float)$a[$i];$y=(float)$b[$i];$dot+=$x*$y;$aa+=$x*$x;$bb+=$y*$y; }
    return ($aa>0 && $bb>0)?$dot/(sqrt($aa)*sqrt($bb)):-1.0;
}
function kce_setting(mysqli $db,string $key,string $default): string {
    $s=$db->prepare('SELECT setting_value FROM kce_settings WHERE setting_key=?');$s->bind_param('s',$key);$s->execute();$v=$s->get_result()->fetch_column();$s->close();return $v===false?$default:(string)$v;
}
function kce_active_sponsors(mysqli $db,array $queryVector): array {
    $sql="SELECT s.*,COALESCE(w.balance,0) wallet_balance, (SELECT COUNT(*) FROM kce_ad_events e WHERE e.sponsored_content_id=s.id AND e.event_type='impression') impressions, (SELECT COUNT(*) FROM kce_ad_events e WHERE e.sponsored_content_id=s.id AND e.event_type='click') clicks FROM kce_sponsored_content s LEFT JOIN kce_advertiser_wallets w ON w.advertiser_id=s.advertiser_id WHERE s.status='active' AND (s.starts_at IS NULL OR s.starts_at<=NOW()) AND (s.ends_at IS NULL OR s.ends_at>=NOW()) AND s.embedding IS NOT NULL";
    $rows=$db->query($sql)->fetch_all(MYSQLI_ASSOC); $ranked=[];
    $defaultImpression=(float)kce_setting($db,'impression_article_cost','0');
    foreach($rows as $r){$impressionCost=$r['impression_unit_cost']!==null?(float)$r['impression_unit_cost']:$defaultImpression;if((float)$r['wallet_balance']<$impressionCost)continue;if($r['max_impressions']!==null && (int)$r['impressions']>=(int)$r['max_impressions'])continue;if($r['max_clicks']!==null&&(int)$r['clicks']>=(int)$r['max_clicks'])continue;$v=json_decode($r['embedding'],true);$r['score']=is_array($v)?kce_cosine($queryVector,$v):-1;$ranked[]=$r; }
    usort($ranked,fn($a,$b)=>$b['score']<=>$a['score']); $threshold=(float)kce_setting($db,'relevance_threshold','0.35');
    return array_slice(array_values(array_filter($ranked,fn($r)=>$r['score'] >= $threshold)),0,(int)kce_setting($db,'max_sponsored_results','2'));
}
function kce_relevant_articles(mysqli $db,array $queryVector): array {
    $sql="SELECT a.id,a.title,a.html_content,a.updated_at,pq.username,e.embedding,e.source_hash FROM kce_article_embeddings e JOIN articles a ON a.id=e.article_id LEFT JOIN publisher_quota pq ON pq.publisher_id=a.publishers_local_id WHERE e.is_active=1 AND a.ispublished=1";
    $result=$db->query($sql);if(!$result)return [];$ranked=[];
    while($r=$result->fetch_assoc()){$vector=json_decode($r['embedding'],true);$r['score']=is_array($vector)?kce_cosine($queryVector,$vector):-1;$plain=trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags($r['html_content']),ENT_QUOTES|ENT_HTML5,'UTF-8')));$r['excerpt']=mb_substr($plain,0,190).(mb_strlen($plain)>190?'…':'');$slug=str_replace(' ','_',preg_replace('/[^A-Za-z0-9 ]/','',(string)$r['title']));$r['url']='/blog/'.rawurlencode((string)($r['username']?:'author')).'/'.(int)$r['id'].'/'.rawurlencode($slug);unset($r['embedding'],$r['html_content']);$ranked[]=$r;}
    usort($ranked,fn($a,$b)=>$b['score']<=>$a['score']);$threshold=(float)kce_setting($db,'article_relevance_threshold','0.30');
    return array_slice(array_values(array_filter($ranked,fn($r)=>$r['score'] >= $threshold)),0,(int)kce_setting($db,'max_article_results','3'));
}
function kce_related_articles_by_article(mysqli $db,int $articleId,int $limit=3): array {
    if ($articleId <= 0 || $limit <= 0) return [];

    $current=$db->prepare('SELECT embedding,embedding_model FROM kce_article_embeddings WHERE article_id=? AND is_active=1 LIMIT 1');
    $current->bind_param('i',$articleId);$current->execute();$source=$current->get_result()->fetch_assoc();$current->close();
    if (!$source) return [];

    $sourceVector=json_decode((string)$source['embedding'],true);
    if (!is_array($sourceVector) || count($sourceVector)!==2048) return [];

    $sql='SELECT a.id,a.title,a.html_content,a.images,pq.username,e.embedding
          FROM kce_article_embeddings e
          JOIN articles a ON a.id=e.article_id
          LEFT JOIN publisher_quota pq ON pq.publisher_id=a.publishers_local_id
          WHERE e.is_active=1 AND e.article_id<>? AND e.embedding_model=? AND a.ispublished=1';
    $stmt=$db->prepare($sql);$model=(string)$source['embedding_model'];$stmt->bind_param('is',$articleId,$model);$stmt->execute();$result=$stmt->get_result();$ranked=[];
    while($row=$result->fetch_assoc()){
        $vector=json_decode((string)$row['embedding'],true);
        if (!is_array($vector) || count($vector)!==2048) continue;
        $row['score']=kce_cosine($sourceVector,$vector);
        $plain=trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags((string)$row['html_content']),ENT_QUOTES|ENT_HTML5,'UTF-8')));
        $row['excerpt']=mb_substr($plain,0,145).(mb_strlen($plain)>145?'…':'');
        $slug=str_replace(' ','_',preg_replace('/[^A-Za-z0-9 ]/','',(string)$row['title']));
        $row['url']='/blog/'.rawurlencode((string)($row['username']?:'author')).'/'.(int)$row['id'].'/'.rawurlencode($slug);
        unset($row['embedding'],$row['html_content']);$ranked[]=$row;
    }
    $stmt->close();usort($ranked,fn($a,$b)=>$b['score']<=>$a['score']);
    return array_slice($ranked,0,$limit);
}
function kce_sponsors_for_article(mysqli $db,int $articleId,int $limit=2): array {
    if($articleId<=0||$limit<=0)return [];
    $stmt=$db->prepare('SELECT embedding FROM kce_article_embeddings WHERE article_id=? AND is_active=1 LIMIT 1');
    $stmt->bind_param('i',$articleId);$stmt->execute();$json=$stmt->get_result()->fetch_column();$stmt->close();
    if($json===false)return [];
    $vector=json_decode((string)$json,true);if(!is_array($vector)||count($vector)!==2048)return [];
    return array_slice(kce_active_sponsors($db,array_map('floatval',$vector)),0,$limit);
}
function kce_sign(int $id,string $conversation): string { return hash_hmac('sha256',$id.'|'.$conversation,(string)(kce_env()['KCE_TRACKING_SECRET'] ?? 'change-me')); }
