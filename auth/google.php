<?php
require_once __DIR__.'/../config/google.php';
require_once __DIR__.'/../config/google_employee_link.php';
method('POST');$b=body();
throttle(hash('sha256','google:'.($_SERVER['REMOTE_ADDR'] ?? 'unknown')));
$claims=verify_google_token(text_value($b,'id_token',12000));
// Never use an email or UID supplied separately by the app. Only verified token claims.
db()->beginTransaction();
try {
 $row=resolve_google_employee(db(),$claims);
 $result=issue_token($row,'employee');
 db()->commit();respond($result);
}catch(InvalidArgumentException $ex){fail($ex->getMessage(),403);}
catch(PDOException $ex){if(in_array((int)($ex->errorInfo[1]??0),[1205,1213],true))fail('มีการผูกบัญชีพร้อมกัน กรุณาลองเข้าสู่ระบบใหม่',409);if($ex->getCode()==='23000')fail('บัญชี Google ถูกผูกกับพนักงานอื่นแล้ว กรุณาติดต่อ HR',409);throw $ex;}
