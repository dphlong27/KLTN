<?php

namespace Database\Seeders;

use App\Models\CongTy;
use App\Models\NganhNghe;
use App\Models\TinTuyenDung;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TinTuyenDungSeeder extends Seeder
{
    public function run(): void
    {
        $congTys = CongTy::all();
        $nganhNghes = NganhNghe::all();

        if ($congTys->isEmpty() || $nganhNghes->isEmpty()) {
            $this->command?->warn('Khong co du lieu cong ty hoac nganh nghe de seed tin tuyen dung.');
            return;
        }

        // Dam bao cong ty co trang thai hoat dong de tin public hien ra
        CongTy::query()->update(['trang_thai' => 1]);

        $mauTinPublic = [
            [
                'tieu_de' => 'Lap trinh vien PHP/Laravel (Senior)',
                'mo_ta_cong_viec' => "Phat trien cac ung dung web su dung Laravel.\nTham gia phan tich thiet ke he thong, review code.\nToi uu hieu nang va bao mat cho ung dung.",
                'dia_diem_lam_viec' => 'Quan 1, TP.HCM',
                'hinh_thuc_lam_viec' => 'full_time',
                'cap_bac' => 'Senior',
                'so_luong_tuyen' => 2,
                'muc_luong' => 30000000,
                'kinh_nghiem_yeu_cau' => '3 - 5 nam',
                'ngay_het_han' => Carbon::now()->addDays(30),
            ],
            [
                'tieu_de' => 'Chuyen vien Marketing Digital',
                'mo_ta_cong_viec' => "Len ke hoach va trien khai cac chien dich quang cao Facebook, Google.\nPhan tich du lieu, toi uu ROI.\nQuan ly doi ngu content creator.",
                'dia_diem_lam_viec' => 'Ha Noi',
                'hinh_thuc_lam_viec' => 'full_time',
                'cap_bac' => 'Truong nhom',
                'so_luong_tuyen' => 1,
                'muc_luong' => 25000000,
                'kinh_nghiem_yeu_cau' => '2 - 3 nam',
                'ngay_het_han' => Carbon::now()->addDays(20),
            ],
            [
                'tieu_de' => 'Thuc tap sinh Frontend (ReactJS)',
                'mo_ta_cong_viec' => "Ho tro cat HTML/CSS tu Figma.\nTham gia phat trien UI components bang ReactJS.\nDuoc dao tao truc tiep voi mentor.",
                'dia_diem_lam_viec' => 'Da Nang',
                'hinh_thuc_lam_viec' => 'internship',
                'cap_bac' => 'Thuc tap sinh',
                'so_luong_tuyen' => 5,
                'muc_luong' => 5000000,
                'kinh_nghiem_yeu_cau' => 'Khong yeu cau',
                'ngay_het_han' => Carbon::now()->addDays(45),
            ],
            [
                'tieu_de' => 'Nhan vien Tuyen dung (HR)',
                'mo_ta_cong_viec' => "Tim kiem, sang loc ho so ung vien.\nTo chuc phong van va danh gia ung vien.\nXay dung van hoa doanh nghiep.",
                'dia_diem_lam_viec' => 'Quan 7, TP.HCM',
                'hinh_thuc_lam_viec' => 'full_time',
                'cap_bac' => 'Nhan vien',
                'so_luong_tuyen' => 2,
                'muc_luong' => 12000000,
                'kinh_nghiem_yeu_cau' => '1 - 2 nam',
                'ngay_het_han' => Carbon::now()->addDays(15),
            ],
            [
                'tieu_de' => 'Designer Part-time',
                'mo_ta_cong_viec' => "Thiet ke banner, poster, social media visual.\nHo tro team marketing va san pham.",
                'dia_diem_lam_viec' => 'Quan 3, TP.HCM',
                'hinh_thuc_lam_viec' => 'part_time',
                'cap_bac' => 'Nhan vien',
                'so_luong_tuyen' => 3,
                'muc_luong' => 8000000,
                'kinh_nghiem_yeu_cau' => 'Duoi 1 nam',
                'ngay_het_han' => Carbon::now()->addDays(25),
            ],
            [
                'tieu_de' => 'Data Analyst Remote',
                'mo_ta_cong_viec' => "Phan tich du lieu kinh doanh.\nXay dung dashboard bao cao.\nDua ra insight ho tro quyet dinh.",
                'dia_diem_lam_viec' => 'Remote',
                'hinh_thuc_lam_viec' => 'remote',
                'cap_bac' => 'Chuyen vien',
                'so_luong_tuyen' => 1,
                'muc_luong' => 45000000,
                'kinh_nghiem_yeu_cau' => 'Tren 3 nam',
                'ngay_het_han' => Carbon::now()->addDays(35),
            ],
        ];

        $congTy1 = $congTys->first();

        foreach ($mauTinPublic as $tin) {
            $record = TinTuyenDung::create(array_merge($tin, [
                'cong_ty_id' => $congTy1->id,
                'trang_thai' => 1,
                'luot_xem' => rand(10, 500),
            ]));

            $record->nganhNghes()->attach(
                $nganhNghes->random(rand(1, 2))->pluck('id')->toArray()
            );
        }

        // Them mot vai tin test an/het han de kiem tra bo loc public
        $tinAn = TinTuyenDung::create([
            'tieu_de' => 'Backend Developer (Tin tam an)',
            'mo_ta_cong_viec' => 'Tin nay dung de test bo loc trang thai.',
            'dia_diem_lam_viec' => 'TP.HCM',
            'hinh_thuc_lam_viec' => 'full_time',
            'cap_bac' => 'Nhan vien',
            'so_luong_tuyen' => 1,
            'muc_luong' => 20000000,
            'kinh_nghiem_yeu_cau' => '2 nam',
            'ngay_het_han' => Carbon::now()->addDays(20),
            'cong_ty_id' => $congTy1->id,
            'trang_thai' => 0,
            'luot_xem' => rand(0, 100),
        ]);
        $tinAn->nganhNghes()->attach($nganhNghes->random(1)->pluck('id')->toArray());

        $tinHetHan = TinTuyenDung::create([
            'tieu_de' => 'QA Engineer (Tin het han)',
            'mo_ta_cong_viec' => 'Tin nay dung de test bo loc ngay het han.',
            'dia_diem_lam_viec' => 'Ha Noi',
            'hinh_thuc_lam_viec' => 'full_time',
            'cap_bac' => 'Nhan vien',
            'so_luong_tuyen' => 1,
            'muc_luong' => 15000000,
            'kinh_nghiem_yeu_cau' => '1 nam',
            'ngay_het_han' => Carbon::now()->subDays(3),
            'cong_ty_id' => $congTy1->id,
            'trang_thai' => 1,
            'luot_xem' => rand(0, 100),
        ]);
        $tinHetHan->nganhNghes()->attach($nganhNghes->random(1)->pluck('id')->toArray());

        // Tao them du lieu random nhung van uu tien hien tren public
        for ($i = 0; $i < 12; $i++) {
            $cty = $congTys->random();

            $record = TinTuyenDung::create([
                'tieu_de' => 'Can tuyen vi tri so ' . ($i + 1),
                'mo_ta_cong_viec' => 'Mo ta cong viec chung mau cho doanh nghiep.',
                'dia_diem_lam_viec' => ['TP.HCM', 'Ha Noi', 'Da Nang', 'Can Tho'][rand(0, 3)],
                'hinh_thuc_lam_viec' => ['full_time', 'part_time', 'internship', 'remote', 'hybrid'][rand(0, 4)],
                'cap_bac' => ['Nhan vien', 'Quan ly', 'Thuc tap sinh'][rand(0, 2)],
                'so_luong_tuyen' => rand(1, 5),
                'muc_luong' => rand(7, 35) * 1000000,
                'kinh_nghiem_yeu_cau' => rand(0, 5) . ' nam',
                'ngay_het_han' => Carbon::now()->addDays(rand(10, 60)),
                'cong_ty_id' => $cty->id,
                'trang_thai' => 1,
                'luot_xem' => rand(0, 200),
            ]);

            $record->nganhNghes()->attach(
                $nganhNghes->random(rand(1, 3))->pluck('id')->toArray()
            );
        }

        $this->command?->info('TinTuyenDungSeeder: da tao du lieu tin tuyen dung mau.');
    }
}
