<?php
declare(strict_types=1);
require dirname(__DIR__).'/lib.php';
if($_SERVER['REQUEST_METHOD']!=='GET')kce_json(['error'=>'Method tidak diizinkan'],405);
$publicId=(string)($_COOKIE['kce_conversation']??'');
if(!preg_match('/^[0-9a-f-]{36}$/i',$publicId))kce_json(['conversation_id'=>'','messages'=>[]]);

try{
    $db=kce_db();
    $s=$db->prepare('SELECT id,role,content FROM (SELECT m.id,m.role,m.content FROM kce_messages m JOIN kce_conversations c ON c.id=m.conversation_id WHERE c.public_id=? AND m.created_at>=DATE_SUB(NOW(),INTERVAL 6 HOUR) ORDER BY m.id DESC LIMIT 40) recent ORDER BY id ASC');
    $s->bind_param('s',$publicId);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();
    $messages=[];$positions=[];$assistantIds=[];$answerIndex=0;
    foreach($rows as $row){$id=(int)$row['id'];if($row['role']==='user')$answerIndex=0;$positions[$id]=count($messages);$message=['role'=>$row['role'],'content'=>$row['content'],'articles'=>[],'sponsored'=>[]];if($row['role']==='assistant'){$answerIndex++;$assistantIds[]=$id;}$messages[]=$message;}

    if($assistantIds){
        $ids=implode(',',array_map('intval',$assistantIds));
        $result=$db->query("SELECT message_id,article_id,title,excerpt,article_url,relevance_score FROM kce_message_articles WHERE message_id IN ($ids) ORDER BY message_id,result_rank");
        while($row=$result->fetch_assoc()){$messageId=(int)$row['message_id'];if(!isset($positions[$messageId]))continue;$messages[$positions[$messageId]]['articles'][]=['id'=>(int)$row['article_id'],'title'=>$row['title'],'excerpt'=>$row['excerpt'],'url'=>$row['article_url'],'score'=>round((float)$row['relevance_score'],4)];}
        $result=$db->query("SELECT message_id,sponsored_content_id,title,body,banner_url,relevance_score FROM kce_message_sponsors WHERE message_id IN ($ids) ORDER BY message_id,result_rank");
        while($row=$result->fetch_assoc()){$messageId=(int)$row['message_id'];if(!isset($positions[$messageId]))continue;$sponsorId=(int)$row['sponsored_content_id'];$messages[$positions[$messageId]]['sponsored'][]=['id'=>$sponsorId,'title'=>$row['title'],'body'=>$row['body'],'banner_url'=>kce_public_url($row['banner_url']),'score'=>round((float)$row['relevance_score'],4),'impression_token'=>kce_sign($sponsorId,$publicId)];}
    }
    kce_json(['conversation_id'=>$publicId,'messages'=>$messages]);
}catch(Throwable $e){error_log('KCE history: '.$e->getMessage());kce_json(['conversation_id'=>$publicId,'messages'=>[]]);}
