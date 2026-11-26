<?php
// Original DD aliases -> physical table names (user-approved naming change, 2026-09-24).
// Aliases remain ID prefixes only, e.g. E0001. DA replaces employee assignments by explicit user request, 2026-10-02.
return [
    'E'=>'employees', 'A'=>'admins', 'H'=>'human_resources', 'S'=>'supervisors',
    'D'=>'departments', 'CFG'=>'system_configurations', 'F'=>'face_biometrics',
    'G'=>'geofence_areas', 'DA'=>'department_work_areas',
    'OS'=>'offsite_work_requests', 'AT'=>'attendance_logs',
];
