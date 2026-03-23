<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HoSoController;
use App\Http\Controllers\Api\NhaTuyenDungHoSoController;
use App\Http\Controllers\Api\NganhNgheController;
use App\Http\Controllers\Api\KyNangController;
use App\Http\Controllers\Api\NguoiDungKyNangController;
use App\Http\Controllers\Api\CongTyController;
use App\Http\Controllers\Api\TinTuyenDungController;
use App\Http\Controllers\Api\NhaTuyenDungCongTyController;
use App\Http\Controllers\Api\NhaTuyenDungTinTuyenDungController;
use App\Http\Controllers\Api\NhaTuyenDungUngTuyenController;
use App\Http\Controllers\Api\UngVienKetQuaMatchingController;
use App\Http\Controllers\Api\UngVienTuVanNgheNghiepController;
use App\Http\Controllers\Api\UngVienLuuTinController;
use App\Http\Controllers\Api\UngVienUngTuyenController;
use App\Http\Controllers\Api\Admin\AdminNguoiDungController;
use App\Http\Controllers\Api\Admin\AdminHoSoController;
use App\Http\Controllers\Api\Admin\AdminNganhNgheController;
use App\Http\Controllers\Api\Admin\AdminKyNangController;
use App\Http\Controllers\Api\Admin\AdminNguoiDungKyNangController;
use App\Http\Controllers\Api\Admin\AdminCongTyController;
use App\Http\Controllers\Api\Admin\AdminTinTuyenDungController;
use App\Http\Controllers\Api\Admin\AdminLuuTinController;
use App\Http\Controllers\Api\Admin\AdminUngTuyenController;
use App\Http\Controllers\Api\Admin\AdminKetQuaMatchingController;
use App\Http\Controllers\Api\Admin\AdminTuVanNgheNghiepController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Module: Người dùng (nguoi_dungs) + Hồ sơ (ho_sos)
|--------------------------------------------------------------------------
|
| Mô hình: API → Methods → Request → Controller → Model
|
| Vai trò:
|   - vai_tro = 0 : ung_vien       (Ứng viên)
|   - vai_tro = 1 : nha_tuyen_dung (Nhà tuyển dụng)
|   - vai_tro = 2 : admin          (Quản trị viên)
|
*/


// ============================================================
// NHÓM 1: PUBLIC — Không cần xác thực
// ============================================================

// Đăng ký tài khoản mới (ứng viên hoặc nhà tuyển dụng)
Route::post('v1/dang-ky', [AuthController::class, 'dangKy'])
    ->name('auth.dang-ky');

// Đăng nhập — trả về Bearer Token
Route::post('v1/dang-nhap', [AuthController::class, 'dangNhap'])
    ->name('auth.dang-nhap');


// ============================================================
// NHÓM 2: AUTH — Cần Bearer Token (tất cả vai trò)
// ============================================================

// Đăng xuất — thu hồi token hiện tại
Route::post('v1/dang-xuat', [AuthController::class, 'dangXuat'])
    ->middleware('auth:sanctum')
    ->name('auth.dang-xuat');

// Xem hồ sơ cá nhân
Route::get('v1/ho-so', [AuthController::class, 'hoSo'])
    ->middleware('auth:sanctum')
    ->name('auth.ho-so');

// Cập nhật hồ sơ cá nhân (hỗ trợ upload ảnh đại diện)
Route::put('v1/cap-nhat-ho-so', [AuthController::class, 'capNhatHoSo'])
    ->middleware('auth:sanctum')
    ->name('auth.cap-nhat-ho-so');

// Đổi mật khẩu — thu hồi tất cả token sau khi đổi thành công
Route::post('v1/doi-mat-khau', [AuthController::class, 'doiMatKhau'])
    ->middleware('auth:sanctum')
    ->name('auth.doi-mat-khau');


// ============================================================
// NHÓM 3: ADMIN — Quản lý người dùng (vai_tro = 2)
// ============================================================

// Thống kê tổng quan người dùng (⚠️ đặt trước /{id} để tránh conflict)
Route::get('v1/admin/nguoi-dungs/thong-ke', [AdminNguoiDungController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nguoi-dungs.thong-ke');

// Danh sách tất cả người dùng (có lọc, tìm kiếm, phân trang)
Route::get('v1/admin/nguoi-dungs', [AdminNguoiDungController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nguoi-dungs.index');

// Tạo tài khoản mới (admin có thể tạo bất kỳ vai trò nào)
Route::post('v1/admin/nguoi-dungs', [AdminNguoiDungController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nguoi-dungs.store');

// Xem chi tiết một người dùng theo ID
Route::get('v1/admin/nguoi-dungs/{id}', [AdminNguoiDungController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nguoi-dungs.show');

// Cập nhật thông tin người dùng theo ID
Route::put('v1/admin/nguoi-dungs/{id}', [AdminNguoiDungController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nguoi-dungs.update');

// Khoá hoặc mở khoá tài khoản (toggle trạng thái)
Route::patch('v1/admin/nguoi-dungs/{id}/khoa', [AdminNguoiDungController::class, 'khoaTaiKhoan'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nguoi-dungs.khoa');

// Xoá tài khoản người dùng theo ID
Route::delete('v1/admin/nguoi-dungs/{id}', [AdminNguoiDungController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nguoi-dungs.destroy');


// ============================================================
// NHÓM 4: ỨNG VIÊN — Quản lý hồ sơ (vai_tro = 0)
// ============================================================

// Danh sách hồ sơ của ứng viên đang đăng nhập
Route::get('v1/ung-vien/ho-sos', [HoSoController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.index');

// Tạo hồ sơ mới (hỗ trợ upload file CV)
Route::post('v1/ung-vien/ho-sos', [HoSoController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.store');

// Xem chi tiết hồ sơ (chỉ xem được của mình)
Route::get('v1/ung-vien/ho-sos/{id}', [HoSoController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.show');

// Cập nhật hồ sơ (chỉ sửa được của mình, hỗ trợ upload file CV)
Route::put('v1/ung-vien/ho-sos/{id}', [HoSoController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.update');

// Xoá hồ sơ (chỉ xoá được của mình)
Route::delete('v1/ung-vien/ho-sos/{id}', [HoSoController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.destroy');

// Đổi trạng thái hồ sơ (công khai/ẩn - toggle)
Route::patch('v1/ung-vien/ho-sos/{id}/trang-thai', [HoSoController::class, 'doiTrangThai'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ho-sos.doi-trang-thai');


// ============================================================
// NHÓM 5: NHÀ TUYỂN DỤNG — Xem hồ sơ ứng viên (vai_tro = 1)
// ============================================================

// Danh sách hồ sơ công khai (có lọc, tìm kiếm, phân trang)
Route::get('v1/nha-tuyen-dung/ho-sos', [NhaTuyenDungHoSoController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.ho-sos.index');

// Xem chi tiết hồ sơ công khai
Route::get('v1/nha-tuyen-dung/ho-sos/{id}', [NhaTuyenDungHoSoController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.ho-sos.show');


// ============================================================
// NHÓM 6: ADMIN — Quản lý hồ sơ ứng viên (vai_tro = 2)
// ============================================================

// Thống kê hồ sơ (⚠️ đặt trước /{id} để tránh conflict)
Route::get('v1/admin/ho-sos/thong-ke', [AdminHoSoController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ho-sos.thong-ke');

// Danh sách hồ sơ đã xoá mềm (thùng rác)
Route::get('v1/admin/ho-sos/da-xoa', [AdminHoSoController::class, 'danhSachDaXoa'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ho-sos.da-xoa');

// Danh sách tất cả hồ sơ (có lọc, tìm kiếm, phân trang)
Route::get('v1/admin/ho-sos', [AdminHoSoController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ho-sos.index');

// Xem chi tiết hồ sơ theo ID
Route::get('v1/admin/ho-sos/{id}', [AdminHoSoController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ho-sos.show');

// Đổi trạng thái hồ sơ (công khai/ẩn)
Route::patch('v1/admin/ho-sos/{id}/trang-thai', [AdminHoSoController::class, 'doiTrangThai'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ho-sos.doi-trang-thai');

// Xoá mềm hồ sơ (soft delete — có thể khôi phục)
Route::delete('v1/admin/ho-sos/{id}', [AdminHoSoController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ho-sos.destroy');

// Khôi phục hồ sơ đã xoá mềm
Route::patch('v1/admin/ho-sos/{id}/khoi-phuc', [AdminHoSoController::class, 'khoiPhuc'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ho-sos.khoi-phuc');


// ============================================================
// NHÓM 7: PUBLIC — Danh mục ngành nghề (không cần xác thực)
// ============================================================

// Danh sách ngành nghề hiển thị (dạng phẳng)
Route::get('v1/nganh-nghes', [NganhNgheController::class, 'index'])
    ->name('nganh-nghes.index');

// Danh sách ngành nghề dạng cây (cha-con)
Route::get('v1/nganh-nghes/cay', [NganhNgheController::class, 'cay'])
    ->name('nganh-nghes.cay');

// Chi tiết ngành nghề
Route::get('v1/nganh-nghes/{id}', [NganhNgheController::class, 'show'])
    ->name('nganh-nghes.show');


// ============================================================
// NHÓM 8: ADMIN — Quản lý ngành nghề (vai_tro = 2)
// ============================================================

// Thống kê ngành nghề (⚠️ đặt trước /{id})
Route::get('v1/admin/nganh-nghes/thong-ke', [AdminNganhNgheController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nganh-nghes.thong-ke');

// Danh sách tất cả ngành nghề (kể cả ẩn)
Route::get('v1/admin/nganh-nghes', [AdminNganhNgheController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nganh-nghes.index');

// Tạo ngành nghề mới
Route::post('v1/admin/nganh-nghes', [AdminNganhNgheController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nganh-nghes.store');

// Chi tiết ngành nghề theo ID
Route::get('v1/admin/nganh-nghes/{id}', [AdminNganhNgheController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nganh-nghes.show');

// Cập nhật ngành nghề
Route::put('v1/admin/nganh-nghes/{id}', [AdminNganhNgheController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nganh-nghes.update');

// Đổi trạng thái (hiển thị/ẩn)
Route::patch('v1/admin/nganh-nghes/{id}/trang-thai', [AdminNganhNgheController::class, 'doiTrangThai'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nganh-nghes.doi-trang-thai');

// Xoá ngành nghề
Route::delete('v1/admin/nganh-nghes/{id}', [AdminNganhNgheController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nganh-nghes.destroy');


// ============================================================
// NHÓM 9: PUBLIC — Danh mục kỹ năng (không cần xác thực)
// ============================================================

// Danh sách kỹ năng
Route::get('v1/ky-nangs', [KyNangController::class, 'index'])
    ->name('ky-nangs.index');

// Chi tiết kỹ năng
Route::get('v1/ky-nangs/{id}', [KyNangController::class, 'show'])
    ->name('ky-nangs.show');


// ============================================================
// NHÓM 10: ADMIN — Quản lý kỹ năng (vai_tro = 2)
// ============================================================

// Thống kê kỹ năng (⚠️ đặt trước /{id})
Route::get('v1/admin/ky-nangs/thong-ke', [AdminKyNangController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ky-nangs.thong-ke');

// Danh sách tất cả kỹ năng
Route::get('v1/admin/ky-nangs', [AdminKyNangController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ky-nangs.index');

// Tạo kỹ năng mới
Route::post('v1/admin/ky-nangs', [AdminKyNangController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ky-nangs.store');

// Chi tiết kỹ năng theo ID
Route::get('v1/admin/ky-nangs/{id}', [AdminKyNangController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ky-nangs.show');

// Cập nhật kỹ năng
Route::put('v1/admin/ky-nangs/{id}', [AdminKyNangController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ky-nangs.update');

// Xoá kỹ năng
Route::delete('v1/admin/ky-nangs/{id}', [AdminKyNangController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ky-nangs.destroy');


// ============================================================
// NHÓM 11: ỨNG VIÊN — Kỹ năng cá nhân (vai_tro = 0)
// ============================================================

// Danh sách kỹ năng của mình
Route::get('v1/ung-vien/ky-nangs', [NguoiDungKyNangController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ky-nangs.index');

// Thêm kỹ năng vào hồ sơ
Route::post('v1/ung-vien/ky-nangs', [NguoiDungKyNangController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ky-nangs.store');

// Cập nhật mức độ / kinh nghiệm / chứng chỉ
Route::put('v1/ung-vien/ky-nangs/{id}', [NguoiDungKyNangController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ky-nangs.update');

// Cập nhật kèm upload ảnh chứng chỉ (dùng POST + _method=PUT cho multipart/form-data)
Route::post('v1/ung-vien/ky-nangs/{id}', [NguoiDungKyNangController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ky-nangs.update-multipart');

// Xoá kỹ năng khỏi hồ sơ
Route::delete('v1/ung-vien/ky-nangs/{id}', [NguoiDungKyNangController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ky-nangs.destroy');


// ============================================================
// NHÓM 19: ỨNG VIÊN — Quản lý tin lưu trữ (vai_tro = 0)
// ============================================================

Route::get('v1/ung-vien/tin-da-luu', [UngVienLuuTinController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.luu-tins.index');

Route::post('v1/ung-vien/tin-da-luu/{tin_id}/toggle', [UngVienLuuTinController::class, 'toggle'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.luu-tins.toggle');


// ============================================================
// NHÓM 21: ỨNG VIÊN — Nộp hồ sơ (Ứng tuyển) (vai_tro = 0)
// ============================================================

Route::get('v1/ung-vien/ung-tuyens', [UngVienUngTuyenController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.index');

Route::post('v1/ung-vien/ung-tuyens', [UngVienUngTuyenController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ung-tuyens.store');


// ============================================================
// NHÓM 24: ỨNG VIÊN — Việc Làm Gợi Ý (AI Matching)
// ============================================================

Route::get('v1/ung-vien/ket-qua-matchings', [UngVienKetQuaMatchingController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.ket-qua-matchings.index');



// ============================================================
// NHÓM 26: ỨNG VIÊN — BÁO CÁO ĐỊNH HƯỚNG NGHỀ (AI Tư vấn)
// ============================================================

Route::get('v1/ung-vien/tu-van-nghe-nghieps', [UngVienTuVanNgheNghiepController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:ung_vien'])
    ->name('ung-vien.tu-van-nghe-nghieps.index');

// ============================================================
// NHÓM 12: ADMIN — Quản lý kỹ năng người dùng (vai_tro = 2)
// ============================================================

// Thống kê (⚠️ đặt trước route có param)
Route::get('v1/admin/nguoi-dung-ky-nangs/thong-ke', [AdminNguoiDungKyNangController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nguoi-dung-ky-nangs.thong-ke');

// Danh sách tất cả bản ghi người dùng — kỹ năng
Route::get('v1/admin/nguoi-dung-ky-nangs', [AdminNguoiDungKyNangController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nguoi-dung-ky-nangs.index');

// Kỹ năng của 1 người dùng cụ thể
Route::get('v1/admin/nguoi-dung-ky-nangs/nguoi-dung/{nguoiDungId}', [AdminNguoiDungKyNangController::class, 'kyNangCuaNguoiDung'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.nguoi-dung-ky-nangs.nguoi-dung');


// ============================================================
// NHÓM 13: PUBLIC — Công ty (không cần xác thực)
// ============================================================

// Danh sách công ty (đang hoạt động)
Route::get('v1/cong-tys', [CongTyController::class, 'index'])
    ->name('cong-tys.index');

// Chi tiết công ty
Route::get('v1/cong-tys/{id}', [CongTyController::class, 'show'])
    ->name('cong-tys.show');

// ============================================================
// NHÓM 16: PUBLIC — Tin tuyển dụng (không cần xác thực)
// ============================================================

Route::get('v1/tin-tuyen-dungs', [TinTuyenDungController::class, 'index'])
    ->name('tin-tuyen-dungs.index');

Route::get('v1/tin-tuyen-dungs/{id}', [TinTuyenDungController::class, 'show'])
    ->name('tin-tuyen-dungs.show');


// ============================================================
// NHÓM 14: NHÀ TUYỂN DỤNG — Quản lý công ty (vai_tro = 1)
// ============================================================

// Xem công ty của mình
Route::get('v1/nha-tuyen-dung/cong-ty', [NhaTuyenDungCongTyController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.cong-ty.show');

// Tạo công ty
Route::post('v1/nha-tuyen-dung/cong-ty', [NhaTuyenDungCongTyController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.cong-ty.store');

// Cập nhật công ty
Route::put('v1/nha-tuyen-dung/cong-ty', [NhaTuyenDungCongTyController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.cong-ty.update');

// ============================================================
// NHÓM 17: NHÀ TUYỂN DỤNG — Quản lý tin tuyển dụng (vai_tro = 1)
// ============================================================

Route::get('v1/nha-tuyen-dung/tin-tuyen-dungs', [NhaTuyenDungTinTuyenDungController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.index');

Route::post('v1/nha-tuyen-dung/tin-tuyen-dungs', [NhaTuyenDungTinTuyenDungController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.store');

Route::get('v1/nha-tuyen-dung/tin-tuyen-dungs/{id}', [NhaTuyenDungTinTuyenDungController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.show');

Route::put('v1/nha-tuyen-dung/tin-tuyen-dungs/{id}', [NhaTuyenDungTinTuyenDungController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.update');

Route::patch('v1/nha-tuyen-dung/tin-tuyen-dungs/{id}/trang-thai', [NhaTuyenDungTinTuyenDungController::class, 'doiTrangThai'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.doi-trang-thai');

Route::delete('v1/nha-tuyen-dung/tin-tuyen-dungs/{id}', [NhaTuyenDungTinTuyenDungController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.tin-tuyen-dungs.destroy');


// ============================================================
// NHÓM 22: NHÀ TUYỂN DỤNG — Duyệt hồ sơ ứng tuyển (vai_tro = 1)
// ============================================================

Route::get('v1/nha-tuyen-dung/ung-tuyens', [NhaTuyenDungUngTuyenController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.ung-tuyens.index');

Route::patch('v1/nha-tuyen-dung/ung-tuyens/{id}/trang-thai', [NhaTuyenDungUngTuyenController::class, 'updateTrangThai'])
    ->middleware(['auth:sanctum', 'role:nha_tuyen_dung'])
    ->name('nha-tuyen-dung.ung-tuyens.update-trang-thai');


// ============================================================
// NHÓM 15: ADMIN — Quản lý công ty (vai_tro = 2)
// ============================================================

// Thống kê (⚠️ đặt trước /{id})
Route::get('v1/admin/cong-tys/thong-ke', [AdminCongTyController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.cong-tys.thong-ke');

// Danh sách tất cả
Route::get('v1/admin/cong-tys', [AdminCongTyController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.cong-tys.index');

// Tạo công ty (Admin)
Route::post('v1/admin/cong-tys', [AdminCongTyController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.cong-tys.store');

// Chi tiết
Route::get('v1/admin/cong-tys/{id}', [AdminCongTyController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.cong-tys.show');

// Cập nhật
Route::put('v1/admin/cong-tys/{id}', [AdminCongTyController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.cong-tys.update');

// Đổi trạng thái
Route::patch('v1/admin/cong-tys/{id}/trang-thai', [AdminCongTyController::class, 'doiTrangThai'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.cong-tys.doi-trang-thai');

// Xoá
Route::delete('v1/admin/cong-tys/{id}', [AdminCongTyController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.cong-tys.destroy');


// ============================================================
// NHÓM 18: ADMIN — Quản lý tin tuyển dụng (vai_tro = 2)
// ============================================================

Route::get('v1/admin/tin-tuyen-dungs/thong-ke', [AdminTinTuyenDungController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.tin-tuyen-dungs.thong-ke');

Route::get('v1/admin/tin-tuyen-dungs', [AdminTinTuyenDungController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.tin-tuyen-dungs.index');

Route::post('v1/admin/tin-tuyen-dungs', [AdminTinTuyenDungController::class, 'store'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.tin-tuyen-dungs.store');

Route::get('v1/admin/tin-tuyen-dungs/{id}', [AdminTinTuyenDungController::class, 'show'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.tin-tuyen-dungs.show');

Route::put('v1/admin/tin-tuyen-dungs/{id}', [AdminTinTuyenDungController::class, 'update'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.tin-tuyen-dungs.update');

Route::patch('v1/admin/tin-tuyen-dungs/{id}/trang-thai', [AdminTinTuyenDungController::class, 'doiTrangThai'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.tin-tuyen-dungs.doi-trang-thai');

Route::delete('v1/admin/tin-tuyen-dungs/{id}', [AdminTinTuyenDungController::class, 'destroy'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.tin-tuyen-dungs.destroy');


// ============================================================
// NHÓM 20: ADMIN — Thống kê lưu tin (vai_tro = 2)
// ============================================================

Route::get('v1/admin/luu-tins/thong-ke', [AdminLuuTinController::class, 'topLuuTin'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.luu-tins.thong-ke');


// ============================================================
// NHÓM 23: ADMIN — Quản lý ứng tuyển (vai_tro = 2)
// ============================================================

Route::get('v1/admin/ung-tuyens/thong-ke', [AdminUngTuyenController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ung-tuyens.thong-ke');

Route::get('v1/admin/ung-tuyens', [AdminUngTuyenController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ung-tuyens.index');

// ============================================================
// NHÓM 25: ADMIN — Quản lý lịch sử AI Matching
// ============================================================

Route::get('v1/admin/ket-qua-matchings/thong-ke', [AdminKetQuaMatchingController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ket-qua-matchings.thong-ke');

Route::get('v1/admin/ket-qua-matchings', [AdminKetQuaMatchingController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.ket-qua-matchings.index');

// ============================================================
// NHÓM 27: ADMIN — Quản lý Hồ sơ Phân Tích (AI Advising)
// ============================================================

Route::get('v1/admin/tu-van-nghe-nghieps/thong-ke', [AdminTuVanNgheNghiepController::class, 'thongKe'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.tu-van-nghe-nghieps.thong-ke');

Route::get('v1/admin/tu-van-nghe-nghieps', [AdminTuVanNgheNghiepController::class, 'index'])
    ->middleware(['auth:sanctum', 'role:admin'])
    ->name('admin.tu-van-nghe-nghieps.index');

