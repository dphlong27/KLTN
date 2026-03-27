<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UngTuyen extends Model
{
    use HasFactory;

    protected $table = 'ung_tuyens';

    protected $fillable = [
        'tin_tuyen_dung_id',
        'ho_so_id',
        'trang_thai',
        'thu_xin_viec',
        'thu_xin_viec_ai',
        'ngay_hen_phong_van',
        'trang_thai_tham_gia_phong_van',
        'thoi_gian_phan_hoi_phong_van',
        'hinh_thuc_phong_van',
        'nguoi_phong_van',
        'link_phong_van',
        'ket_qua_phong_van',
        'ghi_chu',
        'thoi_gian_ung_tuyen'
    ];

    /**
     * Các trạng thái ứng tuyển
     */
    public const TRANG_THAI_CHO_DUYET = 0;
    public const TRANG_THAI_DA_XEM = 1;
    public const TRANG_THAI_CHAP_NHAN = 2;
    public const TRANG_THAI_TU_CHOI = 3;

    public const TRANG_THAI_LIST = [
        self::TRANG_THAI_CHO_DUYET,
        self::TRANG_THAI_DA_XEM,
        self::TRANG_THAI_CHAP_NHAN,
        self::TRANG_THAI_TU_CHOI,
    ];

    public const PHONG_VAN_CHO_XAC_NHAN = 0;
    public const PHONG_VAN_DA_XAC_NHAN = 1;
    public const PHONG_VAN_KHONG_THAM_GIA = 2;

    public const PHONG_VAN_TRANG_THAI_LIST = [
        self::PHONG_VAN_CHO_XAC_NHAN,
        self::PHONG_VAN_DA_XAC_NHAN,
        self::PHONG_VAN_KHONG_THAM_GIA,
    ];

    protected $casts = [
        'thoi_gian_ung_tuyen' => 'datetime',
        'ngay_hen_phong_van' => 'datetime',
        'thoi_gian_phan_hoi_phong_van' => 'datetime',
        'trang_thai' => 'integer',
        'trang_thai_tham_gia_phong_van' => 'integer',
    ];

    /**
     * Tin tuyển dụng mà hồ sơ nộp vào.
     */
    public function tinTuyenDung()
    {
        return $this->belongsTo(TinTuyenDung::class, 'tin_tuyen_dung_id');
    }

    /**
     * Hồ sơ được nộp.
     */
    public function hoSo()
    {
        return $this->belongsTo(HoSo::class, 'ho_so_id')->withTrashed(); // Lấy cả hồ sơ bị xoá mềm để lưu vết
    }
}
