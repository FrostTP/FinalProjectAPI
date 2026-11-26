<?php
declare(strict_types=1);
// Claims must have passed verify_google_token(). Caller owns the transaction.
function google_auto_link_email(array $claims):?string {
 $email=$claims['email']??null;
 if(($claims['email_verified']??false)!==true||!is_string($email)||strlen($email)>100||!filter_var($email,FILTER_VALIDATE_EMAIL))return null;
 $email=strtolower(trim($email));$domain=substr(strrchr($email,'@'),1);
 $hd=$claims['hd']??null;
 // Google is authoritative for Gmail, or a verified Workspace address whose hd matches.
 if($domain!=='gmail.com'&&(!is_string($hd)||strtolower($hd)!==$domain))return null;
 return $email;
}
function resolve_google_employee(PDO $pdo,array $claims):array {
 if(!$pdo->inTransaction())throw new LogicException('Google account binding requires a transaction');
 if(($claims['email_verified']??false)!==true)throw new InvalidArgumentException('อีเมล Google ยังไม่ได้รับการยืนยัน');
 $uid=$claims['sub']??null;
 if(!is_string($uid)||$uid===''||strlen($uid)>50)throw new InvalidArgumentException('Google UID ไม่ถูกต้อง');
 $s=$pdo->prepare('SELECT * FROM employees WHERE google_uid=? FOR UPDATE');$s->execute([$uid]);$bound=$s->fetch(PDO::FETCH_ASSOC);
 if($bound){if($bound['status']!=='active')throw new InvalidArgumentException('บัญชีพนักงานถูกปิดใช้งาน กรุณาติดต่อ HR');return $bound;}
 $email=google_auto_link_email($claims);
 if($email===null)throw new InvalidArgumentException('อีเมลนี้ไม่รองรับการผูกบัญชีอัตโนมัติ กรุณาให้ HR ผูก Google UID: '.$uid);
 $s=$pdo->prepare('SELECT * FROM employees WHERE LOWER(TRIM(email))=? LIMIT 2 FOR UPDATE');$s->execute([$email]);$matches=$s->fetchAll(PDO::FETCH_ASSOC);
 if(count($matches)!==1)throw new InvalidArgumentException(count($matches)>1?'มีข้อมูลพนักงานใช้อีเมลนี้ซ้ำ กรุณาติดต่อ HR':'อีเมล Google นี้ยังไม่ได้ลงทะเบียนเป็นพนักงาน กรุณาติดต่อ HR');
 $row=$matches[0];
 if($row['status']!=='active')throw new InvalidArgumentException('บัญชีพนักงานถูกปิดใช้งาน กรุณาติดต่อ HR');
 if($row['google_uid']!==null&&$row['google_uid']!=='')throw new InvalidArgumentException('อีเมลนี้ผูกกับบัญชี Google อื่นแล้ว กรุณาติดต่อ HR');
 $s=$pdo->prepare("UPDATE employees SET google_uid=? WHERE employee_id=? AND status='active' AND (google_uid IS NULL OR google_uid='')");$s->execute([$uid,$row['employee_id']]);
 if($s->rowCount()!==1)throw new InvalidArgumentException('ข้อมูลบัญชีเปลี่ยนแปลง กรุณาเข้าสู่ระบบใหม่');
 $row['google_uid']=$uid;return $row;
}
