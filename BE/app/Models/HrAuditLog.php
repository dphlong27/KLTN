<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrAuditLog extends Model
{
    use HasFactory;

    protected $table = 'hr_audit_logs';

    protected $fillable = [
        'cong_ty_id',
        'nguoi_thuc_hien_id',
        'nguoi_bi_tac_dong_id',
        'loai_su_kien',
        'mo_ta',
        'du_lieu_bo_sung',
    ];

    protected $casts = [
        'cong_ty_id' => 'integer',
        'nguoi_thuc_hien_id' => 'integer',
        'nguoi_bi_tac_dong_id' => 'integer',
        'du_lieu_bo_sung' => 'array',
    ];

    public function congTy()
    {
        return $this->belongsTo(CongTy::class, 'cong_ty_id');
    }

    public function nguoiThucHien()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_thuc_hien_id');
    }

    public function nguoiBiTacDong()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_bi_tac_dong_id');
    }
}
