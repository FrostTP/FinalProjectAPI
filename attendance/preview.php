<?php
require_once __DIR__.'/../config/face.php';method('POST');$u=employee();$b=body();
$bytes=face_image_bytes($b);
$face=query("SELECT face_encoding FROM face_biometrics WHERE user_id=? AND status='active' AND approval_status='approved'",[$u['id']])->fetch();
if(!$face){fail('ใบหน้ายังไม่ได้รับอนุมัติจาก HR',403);}
$encoding=json_decode($face['face_encoding'],true);
if(!valid_encoding($encoding)){fail('ข้อมูลใบหน้าไม่พร้อม กรุณาติดต่อ HR',409);}
$v=face_call('verify',['image_base64'=>base64_encode($bytes),'reference_encoding'=>$encoding]);
if(($v['verified']??false)!==true){fail('ใบหน้าไม่ตรงกับข้อมูลที่ลงทะเบียน',403);}
$cfg=query("SELECT start_time,start_late FROM system_configurations WHERE config_type='attendance' ORDER BY config_id LIMIT 1")->fetch();
if(!$cfg||!$cfg['start_time']||!$cfg['start_late']){fail('ยังไม่ได้กำหนดเวลาเริ่มงานและเกณฑ์มาสายใน CFG',503);}
$now=time();$late=date('H:i:s',$now)>$cfg['start_late'];
respond(['server_datetime'=>date('Y-m-d H:i:s',$now),'is_late'=>$late,'late_minutes'=>$late?max(1,(int)ceil(($now-strtotime(date('Y-m-d',$now).' '.$cfg['start_time']))/60)):0]);
