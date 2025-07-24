<?php
/**
 * notifikasi_handler.php
 * File ini dipanggil oleh SERVER MIDTRANS, bukan oleh browser pengguna.
 * Tugasnya adalah menerima status akhir pembayaran dan mengupdate database.
 */

// KOREKSI WAJIB: Panggil 'autoload.php', BUKAN 'Midtrans.php'.
// INI ADALAH KUNCI UTAMA AGAR SEMUA CLASS MIDTRANS DIKENALI.
require_once __DIR__ . '/composer/vendor/midtrans/Midtrans.php'; 

// PASTIKAN PATH INI JUGA BENAR SESUAI LOKASI FILE koneksi.php ANDA
require_once __DIR__ . '/backend/koneksi.php';

// Konfigurasi Midtrans (Server Key harus sama dengan yang digunakan untuk membuat token)
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$serverKey = 'SB-Mid-server-m7XR9SAjhEL4DjuqSc1o-qOM'; // GANTI JIKA BERBEDA

try {
    $notif = new \Midtrans\Notification();
} catch (Exception $e) {
    error_log("Error: Notifikasi Midtrans tidak valid. " . $e->getMessage());
    http_response_code(400);
    exit('Error: Invalid notification.');
}

// Ambil data dari notifikasi
$transaction = $notif->transaction_status;
$type = $notif->payment_type;
$order_id = $notif->order_id;
$fraud = $notif->fraud_status;

$status_pesanan_baru = '';
$metode_pembayaran_baru = $type; 

// Tentukan status pesanan baru
if ($transaction == 'capture' || $transaction == 'settlement') {
    if ($fraud == 'accept') {
        $status_pesanan_baru = 'diproses';
    }
} else if ($transaction == 'pending') {
    $status_pesanan_baru = 'menunggu_pembayaran'; 
} else if ($transaction == 'deny' || $transaction == 'cancel' || $transaction == 'expire') {
    $status_pesanan_baru = 'dibatalkan';
}

// Hanya jalankan query jika ada status baru yang valid untuk diupdate
if (!empty($status_pesanan_baru)) {
    
    // Jika pembayaran sukses, lakukan pengurangan stok dan update status dalam satu transaksi
    if ($status_pesanan_baru == 'diproses') {
        mysqli_begin_transaction($koneksi);
        try {
            // LANGKAH A: KURANGI STOK PRODUK
            // 1. Ambil detail item dari pesanan
            $sql_items = "SELECT id_produk, jumlah FROM detail_pesanan WHERE id_pesanan = ?";
            $stmt_items = mysqli_prepare($koneksi, $sql_items);
            mysqli_stmt_bind_param($stmt_items, "s", $order_id);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);

            if (mysqli_num_rows($result_items) == 0) {
                throw new Exception("Detail item untuk pesanan #{$order_id} tidak ditemukan.");
            }

            // 2. Siapkan statement untuk INSERT ke log_stok
            $stmt_log = mysqli_prepare($koneksi, "INSERT INTO log_stok (id_produk, id_kategori_stok, jumlah_perubahan, jenis_aksi, id_pesanan, keterangan) VALUES (?, ?, ?, 'penjualan', ?, ?)");
            $stmt_info = mysqli_prepare($koneksi, "SELECT id_kategori_stok FROM produk WHERE id_produk = ?");

            // 3. Loop setiap item untuk dicatat ke log_stok
            while ($item = mysqli_fetch_assoc($result_items)) {
                mysqli_stmt_bind_param($stmt_info, "s", $item['id_produk']);
                mysqli_stmt_execute($stmt_info);
                $info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));

                $id_produk_log = null;
                $id_kategori_log = null;
                if ($info && !empty($info['id_kategori_stok'])) {
                    $id_kategori_log = $info['id_kategori_stok'];
                } else {
                    $id_produk_log = $item['id_produk'];
                }
                
                $jumlah_pengurangan = -1 * abs($item['jumlah']);
                $keterangan_log = "Penjualan via Midtrans";

                mysqli_stmt_bind_param($stmt_log, "ssiss", $id_produk_log, $id_kategori_log, $jumlah_pengurangan, $order_id, $keterangan_log);
                mysqli_stmt_execute($stmt_log);
            }

            // LANGKAH B: UPDATE STATUS PESANAN
            $stmt_update = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = ?, metode_pembayaran = ? WHERE id_pesanan = ?");
            mysqli_stmt_bind_param($stmt_update, "sss", $status_pesanan_baru, $metode_pembayaran_baru, $order_id);
            mysqli_stmt_execute($stmt_update);

            // Jika semua berhasil, simpan permanen
            mysqli_commit($koneksi);

        } catch (Exception $e) {
            // Jika ada yang gagal, batalkan semua perubahan
            mysqli_rollback($koneksi);
            error_log("TRANSAKSI GAGAL di notifikasi_handler untuk order #{$order_id}: " . $e->getMessage());
            http_response_code(500); // Kirim error ke Midtrans agar dicoba lagi
            exit;
        }

    } else {
        // Untuk status selain 'diproses' (misal: batal, expire), kita hanya update statusnya saja
        $stmt = $koneksi->prepare("UPDATE pesanan SET status_pesanan = ?, metode_pembayaran = ? WHERE id_pesanan = ?");
        $stmt->bind_param("sss", $status_pesanan_baru, $metode_pembayaran_baru, $order_id);
        $stmt->execute();
    }
    
    // Kirim response OK ke Midtrans setelah semua proses selesai
    http_response_code(200);
    echo "Notifikasi berhasil diproses.";

} else {
    // Jika tidak ada status baru, cukup kirim OK
    http_response_code(200);
    echo "Tidak ada perubahan status yang diperlukan.";
}

$koneksi->close();
?>