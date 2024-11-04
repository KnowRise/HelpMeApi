<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Category;
use App\Models\Helper;
use App\Models\Mitra;
use App\Models\MitraService;
use App\Models\Problem;
use App\Models\SubCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // $date = Carbon::now();
        // $year = $date->format('y');
        // $month = $date->format('m');
        // $day = $date->format('d');
        // $identifier = 'ADM' . $year  . $month . $day . '0001';
        // User::create([
        //     'full_name' => env('ADMIN_NAME'),
        //     'username' => env('ADMIN_USERNAME'),
        //     'phone_number' => env('ADMIN_PHONE_NUMBER'),
        //     'password' => bcrypt(env('ADMIN_PASSWORD')),
        //     'image_profile' => 'profiles/default.jpeg',
        //     'identifier' => $identifier,
        //     'is_active' => true,
        //     'phone_number_verified_at' => $date,
        //     'role' => 'admin'
        // ]);

        $date = Carbon::now();
        $year = $date->format('y');
        $month = $date->format('m');
        $day = $date->format('d');
        $identifier = 'ADM' . $year  . $month . $day . '0001';
        User::create([
            'full_name' => 'Admin',
            'username' => 'admin',
            'phone_number' => '08881708331',
            'password' => bcrypt('secret'),
            'image_profile' => 'profiles/default.jpeg',
            'identifier' => $identifier,
            'is_active' => true,
            'phone_number_verified_at' => $date,
            'role' => 'admin'
        ]);

        // User::create([
        //     'full_name' => 'Nadip',
        //     'username' => 'nadip',
        //     'phone_number' => '085211141388',
        //     'password' => bcrypt('nadip121212'),
        //     'image_profile' => 'profiles/default.jpeg',
        //     'is_active' => true,
        //     'role' => 'client',
        //     'identifier' => 'CLNT' . strtoupper(substr(Carbon::now()->format('YmdHis'), 0, 8)) . '0002',
        //     'created_at' => Carbon::now(),
        // ]);

        // User::create([
        //     'full_name' => 'Syifa',
        //     'username' => 'syifa',
        //     'phone_number' => '085861703942',
        //     'password' => bcrypt('syifa212121'),
        //     'image_profile' => 'profiles/default.jpeg',
        //     'is_active' => true,
        //     'role' => 'mitra',
        //     'identifier' => 'MIT' . strtoupper(substr(Carbon::now()->format('YmdHis'), 0, 8)) . '0003',
        //     'created_at' => Carbon::now(),
        // ]);

        // // Seeder untuk User Mitra
        // $active = rand(1, 2);
        // for ($i = 4; $i <= 54; $i++) {
        //     $date = Carbon::now()->subDays(rand(0, 200)); // Menghasilkan tanggal acak dalam 30 hari terakhir
        //     User::create([
        //         'full_name' => 'Mitra ' . $i,
        //         'username' => 'mitra' . $i,
        //         'phone_number' => '12312312038' . $i,
        //         'password' => bcrypt('mitra' . $i),
        //         'image_profile' => 'profiles/default.jpeg',
        //         // 'is_active' => true,
        //         'is_active' => $active == 2 ? true : false,
        //         'role' => 'mitra',
        //         'identifier' => 'MIT' . strtoupper(substr($date->format('YmdHis'), 0, 8)) . str_pad($i, 4, '0', STR_PAD_LEFT),
        //         'created_at' => $date,
        //     ]);
        // }

        // // Seeder untuk User Client
        // $active = rand(1, 2);
        // for ($i = 55; $i <= 85; $i++) {
        //     $date = Carbon::now()->subDays(rand(0, 200)); // Menghasilkan tanggal acak dalam 30 hari terakhir
        //     User::create([
        //         'full_name' => 'Client ' . $i,
        //         'username' => 'client' . $i,
        //         'phone_number' => '1232132132' . $i,
        //         'password' => bcrypt('client' . $i),
        //         'image_profile' => 'profiles/default.jpeg',
        //         // 'is_active' => true,
        //         'is_active' => $active == 2 ? true : false,
        //         'role' => 'client',
        //         'identifier' => 'CLNT' . strtoupper(substr($date->format('YmdHis'), 0, 8)) . str_pad($i, 4, '0', STR_PAD_LEFT),
        //         'created_at' => $date,
        //     ]);
        // }

        $categoryList = [
            'serabutan', 'kendaraan', 'rumah', 'elektronik',
        ];

        // Membuat kategori
        foreach ($categoryList as $categoryName) {
            Category::create([
                'name' => $categoryName
            ]);
        }

        $helperList = [
            // Helper untuk kategori 'serabutan'
            ['terapis urut', 'terapis pijat', 'semua serabutan'],
            // Helper untuk kategori 'kendaraan'
            ['tukang tambal ban', 'montir motor', 'ahli kunci', 'cuci kendaraan', 'montir mobil', 'montir ac mobil', 'montir sepeda'],
            // Helper untuk kategori 'rumah'
            ['tukang ledeng', 'usaha sedot wc', 'tukang listrik', 'ahli kunci', 'tukang bangunan'],
            // Helper untuk kategori 'elektronik'
            ['teknisi ac', 'teknisi kulkas', 'teknisi mesin cuci', 'teknisi tv', 'teknisi laptop', 'teknisi hp'],
        ];

        $categoryListCount = count($categoryList);
        // Membuat helper berdasarkan kategori
        for ($i = 0; $i < $categoryListCount; $i++) {
            foreach ($helperList[$i] as $helperName) {
                Helper::create([
                    'name' => $helperName,
                    'category_id' => $i + 1 // Pastikan category_id sesuai
                ]);
            }
        }

        $problemList = [
            // Masalah untuk kategori 'serabutan'
            ['keseleo'],
            // Masalah untuk kategori 'serabutan'
            ['butuh pijet'],
            // Masalah untuk kategori 'serabutan'
            ['lainnya'],
            // Masalah untuk kategori 'kendaraan'
            ['ban motor kempes', 'ban mobil kempes', 'ban sepeda kempes'],
            // Masalah untuk kategori 'kendaraan'
            ['motor mogok', 'motor perlu service ringan'],
            // Masalah untuk kategori 'kendaraan'
            ['kunci motor hilang/rusak', 'kunci mobil hilang/rusak'],
            // Masalah untuk kategori 'kendaraan'
            ['motor perlu dicuci', 'mobil perlu dicuci'],
            // Masalah untuk kategori 'kendaraan'
            ['mobil mogok', 'mobil perlu service ringan'],
            // Masalah untuk kategori 'kendaraan'
            ['ac mobil ga dingin'],
            // Masalah untuk kategori 'kendaraan'
            ['setel sepeda'],
            // Masalah untuk kategori 'rumah'
            ['masalah air/ledeng'],
            // Masalah untuk kategori 'rumah'
            ['septic tank penuh'],
            // Masalah untuk kategori 'rumah'
            ['masalah listrik'],
            // Masalah untuk kategori 'rumah'
            ['kunci rumah hilang/rusak'],
            // Masalah untuk kategori 'rumah'
            ['panggil tukang bangunan'],
            // Masalah untuk kategori 'elektronik'
            ['masalah ac'],
            // Masalah untuk kategori 'elektronik'
            ['masalah kulkas'],
            // Masalah untuk kategori 'elektronik'
            ['masalah mesin cuci'],
            // Masalah untuk kategori 'elektronik'
            ['masalah tv'],
            // Masalah untuk kategori 'elektronik'
            ['masalah laptop'],
            // Masalah untuk kategori 'elektronik'
            ['masalah hp'],
        ];

        $countProblemList = count($problemList);
        // Membuat masalah berdasarkan helper
        for ($i = 0; $i < $countProblemList; $i++) {
            foreach ($problemList[$i] as $problemName) {
                Problem::create([
                    'name' => $problemName,
                    'helper_id' => $i + 1 // Menyesuaikan dengan index kategori
                ]);
            }
        }
    }
}
