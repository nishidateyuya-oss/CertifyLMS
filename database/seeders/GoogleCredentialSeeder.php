<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GoogleCredential;
use App\Models\User;
use Illuminate\Database\Seeder;

class GoogleCredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. 連携済みのコーチ (Google 連携データを作成)
        // role が coach かつ連携テストに使用したいコーチユーザーを指定
        $connectedCoach = User::factory()->create(['role' => 'coach', 'name' => '連携済みコーチ']);

        $unconnectedCoach = User::factory()->create([
            'role' => 'coach',
            'name' => '未連携コーチ',
        ]);

        // 未連携コーチの Credentials レコードが存在する場合は確実に削除
        GoogleCredential::where('user_id', $unconnectedCoach->id)->delete();
    }
}
