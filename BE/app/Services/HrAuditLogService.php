<?php

namespace App\Services;

use App\Models\CongTy;
use App\Models\HrAuditLog;
use App\Models\NguoiDung;

class HrAuditLogService
{
    public function log(
        CongTy $congTy,
        ?NguoiDung $actor,
        string $eventType,
        string $description,
        ?NguoiDung $target = null,
        array $extra = [],
    ): HrAuditLog {
        return HrAuditLog::create([
            'cong_ty_id' => $congTy->id,
            'nguoi_thuc_hien_id' => $actor?->id,
            'nguoi_bi_tac_dong_id' => $target?->id,
            'loai_su_kien' => $eventType,
            'mo_ta' => $description,
            'du_lieu_bo_sung' => $extra ?: null,
        ]);
    }
}
