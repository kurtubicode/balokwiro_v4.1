<?php

require_once dirname(__FILE__) . '/composer/vendor/midtrans/Midtrans.php';
include 'includes/koneksi.php';

\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$serverKey = 'SB-Mid-server-m7XR9SAjhEL4DjuqSc1o-qOM'; // GANTI DENGAN SERVER KEY ANDA

try {
    $notif = new \Midtrans\Notification();
} catch (Exception $e) {
    error_log("Error creating notification object: " . $e->getMessage());
    exit('Error');
}

$transaction = $notif->transaction_status;
$type = $notif->payment_type;
$order_id = $notif->order_id;
$fraud = $notif->fraud_status;

// Inisialisasi status pesanan
$status_pesanan = '';

if ($transaction == 'capture') {
    // Untuk transaksi kartu kredit, 'capture' berarti pembayaran berhasil
    if ($type == 'credit_card') {
        if ($fraud == 'challenge') {
            // TODO: Atur status pesanan menjadi 'challenge' atau 'tertahan'
            $status_pesanan = 'challenge';
        } else {
            // TODO: Atur status pesanan menjadi 'diproses'
            $status_pesanan = 'diproses';
        }
    }
} else if ($transaction == 'settlement') {
    // Untuk metode pembayaran lain, 'settlement' berarti pembayaran berhasil
    // TODO: Atur status pesanan menjadi 'diproses'
    $status_pesanan = 'diproses';
} else if ($transaction == 'pending') {
    // TODO: Atur status pesanan menjadi 'menunggu_pembayaran'
    $status_pesanan = 'menunggu_pembayaran';
} else if ($transaction == 'deny') {
    // TODO: Atur status pesanan menjadi 'dibatalkan'
    $status_pesanan = 'dibatalkan';
} else if ($transaction == 'expire') {
    // TODO: Atur status pesanan menjadi 'dibatalkan' atau 'kedaluwarsa'
    $status_pesanan = 'kedaluwarsa';
} else if ($transaction == 'cancel') {
    // TODO: Atur status pesanan menjadi 'dibatalkan'
    $status_pesanan = 'dibatalkan';
}

// Jika status pesanan sudah ditentukan, update database
if (!empty($status_pesanan)) {
    // Gunakan prepared statement untuk keamanan
    $stmt = $koneksi->prepare("UPDATE pesanan SET status_pesanan = ? WHERE id_pesanan = ?");
    $stmt->bind_param("ss", $status_pesanan, $order_id);

    if ($stmt->execute()) {
        // Jika statusnya 'diproses', Anda bisa menambahkan logika untuk mengurangi stok di sini
        if ($status_pesanan == 'diproses') {
            // ... kode untuk mengurangi stok ...
        }
        // Beri respons OK ke Midtrans
        http_response_code(200);
        echo "OK";
    } else {
        // Jika gagal update, catat error
        error_log("Gagal update status pesanan untuk order_id: " . $order_id);
        http_response_code(500);
    }
    $stmt->close();
}

$koneksi->close();
?>