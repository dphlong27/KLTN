<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CongTyLoiMoi extends Model
{
    use HasFactory;

    protected $table = 'cong_ty_loi_mois';

    protected $fillable = [
        'cong_ty_id',
        'nguoi_dung_id',
        'email',
        'vai_tro_noi_bo',
        'trang_thai',
        'duoc_moi_boi',
        'phan_hoi_luc',
    ];

    protected $casts = [
        'cong_ty_id' => 'integer',
        'nguoi_dung_id' => 'integer',
        'duoc_moi_boi' => 'integer',
        'phan_hoi_luc' => 'datetime',
    ];

    const TRANG_THAI_DANG_CHO = 'pending';
    const TRANG_THAI_CHAP_NHAN = 'accepted';
    const TRANG_THAI_TU_CHOI = 'rejected';
    const TRANG_THAI_DA_HUY = 'cancelled';

    public function congTy()
    {
        return $this->belongsTo(CongTy::class, 'cong_ty_id');
    }

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function nguoiMoi()
    {
        return $this->belongsTo(NguoiDung::class, 'duoc_moi_boi');
    }
}
