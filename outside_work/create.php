<?php
require_once __DIR__.'/../config/face.php';method('POST');$u=employee();$b=body();
$start=date_value($b,'work_date_start');$end=date_value($b,'work_date_end');if($start<date('Y-m-d')||$end<$start){fail('ช่วงวันไม่ถูกต้อง');}
$location=text_value($b,'location_description',255);$reason=text_value($b,'reason',255);
if(isset($b['departure_time'])||isset($b['return_time'])){
 $departure=$b['departure_time']??'';$return=$b['return_time']??'';
 if(!is_string($departure)||!is_string($return)||!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/D',$departure)||!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/D',$return)||($start===$end&&$return<=$departure)){fail('เวลาออกและเวลากลับไม่ถูกต้อง');}
 $reason.="\nเวลาออก $departure น. • เวลากลับ $return น.";
 if(mb_strlen($reason)>255){fail('เหตุผลรวมช่วงเวลาต้องไม่เกิน 255 ตัวอักษร');}
}
$bytes=isset($b['evidence_image_base64'])?face_image_bytes(['image_base64'=>$b['evidence_image_base64']]):null;
$path=null;$metadata=null;
try {
 db()->beginTransaction();$id=next_id('OS');
 query("INSERT INTO offsite_work_requests VALUES(?,?,NOW(),?,?,?,?,'รออนุมัติ',NULL,NULL)",[$id,$u['id'],$start,$end,$location,$reason]);
 if($bytes!==null){
  $path=save_face_image($bytes);$metadata=__DIR__.'/../storage/offsite-'.$id.'.json';
  if(file_put_contents($metadata,json_encode(['evidence_photo'=>$path]),LOCK_EX)===false)throw new RuntimeException('Cannot save offsite attachment');
 }
 notice($u['id'],'ส่งคำขอนอกพื้นที่แล้ว',$location);db()->commit();
}catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();if($path)@unlink(__DIR__.'/../'.$path);if($metadata)@unlink($metadata);throw $ex;}
respond(['offsite_request_id'=>$id],'บันทึกคำขอแล้ว',201);
