<?php

namespace Database\Factories;

use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NguoiDung>
 */
class NguoiDungFactory extends Factory
{
    protected $model = NguoiDung::class;

    protected static ?string $password;

    public function definition(): array
    {
        $hoTen = $this->taoHoTenTiengViet();
        $tinhThanh = [
            'Hà Nội',
            'TP. Hồ Chí Minh',
            'Đà Nẵng',
            'Hải Phòng',
            'Cần Thơ',
            'An Giang',
            'Bình Dương',
            'Đồng Nai',
        ];
        $duongPho = [
            'Nguyễn Trãi',
            'Lê Lợi',
            'Trần Hưng Đạo',
            'Phan Chu Trinh',
            'Cách Mạng Tháng 8',
            'Điện Biên Phủ',
            'Hoàng Văn Thụ',
            'Nguyễn Văn Linh',
            'Lý Thường Kiệt',
            'Võ Văn Tần',
        ];

        return [
            'ho_ten' => $hoTen,
            'email' => $this->taoEmailTuHoTen($hoTen),
            'mat_khau' => static::$password ??= Hash::make('password123'),
            'so_dien_thoai' => '0' . $this->faker->numerify('#########'),
            'ngay_sinh' => $this->faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'gioi_tinh' => $this->faker->randomElement(['nam', 'nu', 'khac']),
            'dia_chi' => $this->faker->numberBetween(10, 250) . ' ' . $this->faker->randomElement($duongPho) . ', ' . $this->faker->randomElement($tinhThanh),
            'anh_dai_dien' => null,
            'vai_tro' => NguoiDung::VAI_TRO_UNG_VIEN,
            'trang_thai' => 1,
            'remember_token' => Str::random(10),
        ];
    }

    /** Tạo tài khoản Admin */
    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'vai_tro' => NguoiDung::VAI_TRO_ADMIN,
        ]);
    }

    /** Tạo tài khoản Nhà tuyển dụng */
    public function nhaTuyenDung(): static
    {
        return $this->state(fn(array $attributes) => [
            'vai_tro' => NguoiDung::VAI_TRO_NHA_TUYEN_DUNG,
        ]);
    }

    /** Tạo tài khoản Ứng viên */
    public function ungVien(): static
    {
        return $this->state(fn(array $attributes) => [
            'vai_tro' => NguoiDung::VAI_TRO_UNG_VIEN,
        ]);
    }

    /** Tạo tài khoản bị vô hiệu hóa */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'trang_thai' => 0,
        ]);
    }

    private function taoHoTenTiengViet(): string
    {
        $ho = [
            'Nguyễn',
            'Trần',
            'Lê',
            'Phạm',
            'Hoàng',
            'Huỳnh',
            'Phan',
            'Vũ',
            'Võ',
            'Đặng',
            'Bùi',
            'Đỗ',
        ];

        $tenDem = [
            'Văn',
            'Thị',
            'Minh',
            'Ngọc',
            'Gia',
            'Khánh',
            'Quốc',
            'Thanh',
            'Hoàng',
            'Anh',
            'Đức',
            'Thu',
        ];

        $ten = [
            'An',
            'Bình',
            'Châu',
            'Duy',
            'Giang',
            'Hân',
            'Hùng',
            'Khôi',
            'Linh',
            'Long',
            'Minh',
            'My',
            'Nam',
            'Ngân',
            'Ngọc',
            'Nhật',
            'Nhi',
            'Phúc',
            'Phương',
            'Quân',
            'Sơn',
            'Thảo',
            'Thịnh',
            'Trang',
            'Trúc',
            'Tuấn',
            'Vy',
            'Yến',
        ];

        return implode(' ', [
            $this->faker->randomElement($ho),
            $this->faker->randomElement($tenDem),
            $this->faker->randomElement($ten),
        ]);
    }

    private function taoEmailTuHoTen(string $hoTen): string
    {
        $slug = Str::slug($hoTen, '.');
        $domain = $this->faker->randomElement([
            'gmail.com',
            'outlook.com',
            'yahoo.com',
            'kltn-mail.com',
        ]);

        return $this->faker->unique()->numerify($slug . '###@' . $domain);
    }
}
