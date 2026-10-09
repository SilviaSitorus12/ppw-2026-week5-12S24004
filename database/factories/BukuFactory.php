<?php

namespace Database\Factories;

use App\Models\Kategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Buku>
 */
class BukuFactory extends Factory
{
    /** Penerbit nyata di Indonesia agar data terlihat realistis. */
    private const PENERBIT = [
        'Andi Offset', 'Informatika Bandung', 'Elex Media Komputindo',
        'Gramedia Pustaka Utama', 'Erlangga', 'Deepublish',
        'Salemba Empat', 'Kencana Prenada Media', 'Mizan', 'Bentang Pustaka',
    ];

    private const TOPIK_UMUM = [
        'Basis Data', 'Pemrograman Web', 'Jaringan Komputer', 'Data Mining',
        'Rekayasa Perangkat Lunak', 'Sistem Informasi Manajemen', 'Statistika',
    ];

    private const POLA_JUDUL = [
        'Pengantar %s', 'Dasar-Dasar %s', '%s untuk Pemula',
        'Panduan Praktis %s', '%s: Teori dan Aplikasi',
    ];

    /**
     * State bawaan: dipakai jika factory dipanggil tanpa state tambahan,
     * misalnya Buku::factory()->count(10)->create().
     */
    public function definition(): array
    {
        $judul = sprintf(fake()->randomElement(self::POLA_JUDUL), fake()->randomElement(self::TOPIK_UMUM));

        return [
            'isbn'         => fake()->unique()->isbn13(),
            'judul'        => $judul,
            'penulis'      => fake()->name(),
            'penerbit'     => fake()->randomElement(self::PENERBIT),
            'tahun_terbit' => fake()->numberBetween(2005, (int) date('Y')),
            'kategori_id'  => Kategori::factory(),
            'stok'         => fake()->numberBetween(0, 25),
            'sinopsis'     => self::sinopsisNonFiksi(),
        ];
    }

    /**
     * State khusus: memberi judul tertentu beserta sinopsis yang sesuai jenisnya.
     */
    public function denganJudul(string $judul, bool $fiksi = false): static
    {
        return $this->state(fn () => [
            'judul'    => $judul,
            'sinopsis' => $fiksi ? self::sinopsisFiksi() : self::sinopsisNonFiksi(),
        ]);
    }

    private static function sinopsisNonFiksi(): string
    {
        $pembuka = fake()->randomElement([
            'Buku ini menyajikan pembahasan yang sistematis, mulai dari konsep dasar hingga studi kasus nyata di dunia industri.',
            'Ditulis dengan bahasa yang mudah dipahami, buku ini cocok menjadi pegangan mahasiswa maupun praktisi.',
            'Edisi ini diperbarui mengikuti perkembangan terbaru dan memuat contoh penerapan di organisasi Indonesia.',
        ]);
        $penutup = fake()->randomElement([
            'Setiap bab dilengkapi ringkasan, latihan soal, dan bahan diskusi kelompok.',
            'Pembaca juga dibimbing mengerjakan proyek kecil agar teori langsung dapat dipraktikkan.',
            'Lampiran berisi glosarium istilah serta daftar referensi untuk pendalaman materi.',
        ]);

        return "{$pembuka} {$penutup}";
    }

    private static function sinopsisFiksi(): string
    {
        $tokoh = fake()->firstName();

        return fake()->randomElement([
            "Kisah {$tokoh}, seorang perantau yang kembali ke kampung halamannya dan menemukan bahwa banyak hal telah berubah, termasuk dirinya sendiri.",
            "{$tokoh} harus memilih antara mengejar mimpi di kota besar atau menjaga warisan keluarga yang hampir terlupakan.",
            "Sebuah novel tentang persahabatan, kehilangan, dan keberanian, dituturkan melalui mata {$tokoh} selama satu musim hujan yang panjang.",
        ]);
    }
}