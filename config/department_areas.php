<?php
declare(strict_types=1);
// Shared web/API rules for department-wide assignments (2026-10-02).
function configured_campus_id(): string {
    $file=getenv('TIMEGUARD_CAMPUS_LAYER_FILE')?:dirname(__DIR__,2).'/timeguard_admin/config/campus-layer.local.json';
    $data=is_file($file)?json_decode((string)file_get_contents($file),true):null;
    return is_array($data)&&is_string($data['area_id']??null)?$data['area_id']:'';
}
function validate_department_area(PDO $pdo,array $values,string $id=''): void {
    if(!$pdo->inTransaction())throw new LogicException('Assignment validation requires a transaction');
    $select=static function(string $sql,array $args)use($pdo):mixed{$s=$pdo->prepare($sql);$s->execute($args);return $s->fetch(PDO::FETCH_ASSOC);};
    $department=$select('SELECT department_id,status FROM departments WHERE department_id=? FOR UPDATE',[$values['department_id']]);
    if(!$department)throw new InvalidArgumentException('ไม่พบแผนก');
    if($id!==''){
        $old=$select('SELECT department_id FROM department_work_areas WHERE department_area_id=? FOR UPDATE',[$id]);
        if(!$old||$old['department_id']!==$values['department_id'])throw new InvalidArgumentException('ไม่สามารถย้ายรายการมอบหมายไปแผนกอื่น');
    }
    // Block new duplicates while allowing old records to be disabled.
    if($id===''||$values['status']==='active'){
        $existing=$select("SELECT department_area_id FROM department_work_areas
            WHERE department_id=? AND department_area_id<>? AND (?='' OR status='active') LIMIT 1 FOR UPDATE",
            [$values['department_id'],$id,$id]);
        if($existing)throw new InvalidArgumentException($id===''
            ?'แผนกนี้มีพื้นที่แล้ว กรุณาแก้ไขรายการเดิม'
            :'แผนกนี้มีพื้นที่อื่นที่ใช้งานอยู่ กรุณาปิดรายการนั้นก่อน');
    }
    if($values['end_date']!==null&&$values['end_date']<$values['effective_date'])throw new InvalidArgumentException('วันสิ้นสุดต้องไม่ก่อนวันเริ่ม');
    $area=$select('SELECT area_id,status FROM geofence_areas WHERE area_id=?',[$values['area_id']]);
    if(!$area)throw new InvalidArgumentException('ไม่พบพื้นที่ปฏิบัติงาน');
    // Permit deactivation of legacy configurations even when their area was deactivated/reassigned.
    if($values['status']!=='active')return;
    if($values['area_id']===configured_campus_id())throw new InvalidArgumentException('พื้นที่ส่วนกลางเป็นขอบเขตหลัก กรุณาเลือกพื้นที่ทำงานของแผนก');
    if($department['status']!=='active'||$area['status']!=='active')throw new InvalidArgumentException('แผนกและพื้นที่ต้องมีสถานะใช้งาน');
}
