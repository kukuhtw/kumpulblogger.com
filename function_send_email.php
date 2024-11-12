<?php

// 

function sendmail($from,$to,$subject,$text,$SMTP_API_KEY,$DOMAIN_NAME) {

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://aplikasi.kirim.email/api/v3/transactional/messages');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);

    

    $post = array(
        'from' => $from,
        'to' => $to,
        'subject' => $subject,
        'text' => $text
    );
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_USERPWD, 'api' . ':' . $SMTP_API_KEY);

     //echo "<br>post: ".json_encode($post);

    //$headers = array();
    //$headers[] = 'Domain: $DOMAIN_NAME';
    //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $headers = array();
    $headers[] = 'Domain: KumpulBlogger.com';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);

   // echo "<br>DOMAIN_NAME: ".$DOMAIN_NAME;
    //echo "<br>result: ".$result;

    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);        

}


?>

