<?php
// Konfigurasi Koneksi Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "laundry_app"; // Sesuaikan nama database kamu

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Cek apakah form dikirim
if (isset($_POST['pesan_sekarang'])) {
    $id_paket  = mysqli_real_escape_string($conn, $_POST['id_paket']);
    $nama      = mysqli_real_escape_string($conn, $_POST['nama']);
    $telepon   = mysqli_real_escape_string($conn, $_POST['telepon']);
    $id_outlet = mysqli_real_escape_string($conn, $_POST['id_outlet']);
    $alamat    = mysqli_real_escape_string($conn, $_POST['alamat']);

    // 1. Cek apakah member sudah terdaftar berdasarkan nomor telepon
    $cek_member = mysqli_query($conn, "SELECT id FROM tb_member WHERE tlp = '$telepon'");
    
    if (mysqli_num_rows($cek_member) > 0) {
        $row_member = mysqli_fetch_assoc($cek_member);
        $id_member = $row_member['id'];
    } else {
        // Jika belum ada, otomatis daftarkan sebagai member baru
        mysqli_query($conn, "INSERT INTO tb_member (nama, alamat, jenis_kelamin, tlp) VALUES ('$nama', '$alamat', 'L', '$telepon')");
        $id_member = mysqli_insert_id($conn);
    }

    // 2. Generate Kode Invoice Unik
    $kode_invoice = "TRX-" . date('YmdHis');
    $tgl = date('Y-m-d H:i:s');
    // Perkiraan selesai 3 hari ke depan
    $batas_waktu = date('Y-m-d H:i:s', strtotime('+3 days')); 

    // 3. Masukkan data ke tabel transaksi
    $query_transaksi = "INSERT INTO tb_transaksi (id_outlet, kode_invoice, id_member, tgl, batas_waktu, tgl_bayar, biaya_tambahan, diskon, pajak, status, dibayar, id_user) 
                        VALUES ('$id_outlet', '$kode_invoice', '$id_member', '$tgl', '$batas_waktu', NULL, 0, 0, 0, 'baru', 'belum_lunas', 1)";
    
    if (mysqli_query($conn, $query_transaksi)) {
        $id_transaksi = mysqli_insert_id($conn);

        // 4. Masukkan data ke tabel detail_transaksi (Asumsi kuantitas awal 1 kg atau pcs)
        $qty = 1; 
        $query_detail = "INSERT INTO tb_detail_transaksi (id_transaksi, id_paket, qty, keterangan) 
                         VALUES ('$id_transaksi', '$id_paket', '$qty', 'Pesanan via web online')";
        mysqli_query($conn, $query_detail);

        // 5. Redirect kembali ke index dengan membawa nomor invoice
        header("location: index.php?status=sukses&invoice=" . $kode_invoice);
        exit();
    } else {
        echo "Gagal memproses transaksi: " . mysqli_error($conn);
    }
} else {
    // Jika diakses secara langsung tanpa isi form
    header("location: index.php");
    exit();
}
?>