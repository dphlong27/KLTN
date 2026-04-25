<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmartJobAlert extends Model
{
    use HasFactory;

    public const TRANG_THAI_MOI = 'new';
    public const TRANG_THAI_DA_DOC = 'read';
    public const TRANG_THAI_BO_QUA = 'dismissed';

    protected $fillable = [
        'nguoi_dung_id',
        'tin_tuyen_dung_id',
        'cong_ty_id',
        'ho_so_id',
        'match_score',
        'match_level',
        'matched_skills_json',
        'missing_skills_json',
        'matched_industries_json',
        'reasons_json',
        'metadata_json',
        'trang_thai',
        'notified_at',
        'read_at',
        'dismissed_at',
    ];

    protected $casts = [
        'nguoi_dung_id' => 'integer',
        'tin_tuyen_dung_id' => 'integer',
        'cong_ty_id' => 'integer',
        'ho_so_id' => 'integer',
        'match_score' => 'float',
        'matched_skills_json' => 'array',
        'missing_skills_json' => 'array',
        'matched_industries_json' => 'array',
        'reasons_json' => 'array',
        'metadata_json' => 'array',
        'notified_at' => 'datetime',
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function nguoiDung()
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function tinTuyenDung()
    {
        return $this->belongsTo(TinTuyenDung::class, 'tin_tuyen_dung_id');
    }

    public function congTy()
    {
        return $this->belongsTo(CongTy::class, 'cong_ty_id');
    }

    public function hoSo()
    {
        return $this->belongsTo(HoSo::class, 'ho_so_id')->withTrashed();
    }
}
