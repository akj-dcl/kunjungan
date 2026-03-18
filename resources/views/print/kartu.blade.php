<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Izin Kunjungan</title>
    <style>
        /* Setup Kertas A5 dan Margin diperkecil agar muat 1 halaman */
        @page {
            size: A5 portrait; 
            margin: 0.8cm; /* Diperkecil dari 1.5cm */
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 11px;
            color: #000;
        }

        /* HEADER KOP SURAT */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 3px solid black;
            padding-bottom: 5px;
            margin-bottom: 10px; /* Diperkecil */
        }
        
        .kop-logo {
            width: 65px; /* Diperkecil sedikit */
            height: auto;
            margin-right: 15px;
        }

        .kop-text {
            flex: 1;
            text-align: center;
        }
        
        .kop-text h1 { font-size: 12px; margin: 0; font-weight: bold; }
        .kop-text h2 { font-size: 13px; margin: 2px 0; font-weight: bold; }
        .kop-text h3 { font-size: 11px; margin: 0; font-weight: bold; }
        .kop-text p { font-size: 9px; margin: 2px 0 0; }

        /* JUDUL SURAT */
        .judul-surat {
            text-align: center;
            margin-bottom: 10px; /* Diperkecil dari 20px */
        }
        .judul-surat h4 {
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
        }

        /* TABEL INFORMASI UMUM (Tanpa Garis) */
        table.info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px; /* Diperkecil */
        }
        table.info-table td {
            vertical-align: top;
            padding: 2px 0; /* Diperkecil */
        }
        .col-label { width: 130px; }
        .col-titik { width: 15px; text-align: center; }

        /* TABEL BARANG TITIPAN (Dengan Garis) */
        .barang-title { margin-bottom: 2px; }
        table.barang-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px; /* Diperkecil */
        }
        table.barang-table th, table.barang-table td {
            border: 1px solid black;
            padding: 4px; /* Diperkecil */
            text-align: left;
        }

        /* FOOTER SECTION (TANDA TANGAN) */
        .footer-section {
            margin-top: 15px; /* Diperkecil dari 30px */
            width: 100%;
            display: flex;
            justify-content: flex-end; 
            page-break-inside: avoid; 
            break-inside: avoid;
        }

        .tanda-tangan {
            text-align: center;
            width: 180px; 
        }
        .tanda-tangan .tanggal { margin-bottom: 5px; }
        .tanda-tangan .ttd-space { height: 60px; /* Diperkecil sedikit */ }
        .tanda-tangan .nama-petugas { 
            border-bottom: 1px solid black; 
            padding-bottom: 2px;
            margin-bottom: 2px;
        }

        /* CATATAN BAWAH */
        .catatan {
            margin-top: 10px; /* Diperkecil dari 20px */
            color: red;
            font-size: 9px; /* Diperkecil sedikit */
            font-style: italic;
            page-break-inside: avoid; 
            break-inside: avoid;
        }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print(); window.onafterprint = function(){ window.close(); };">

    <div class="nomor-antrian">{{ str_pad($kunjungan->id, 3, '0', STR_PAD_LEFT) }}</div>

    <div class="kop-surat">
        <img src="{{ asset('images/logo.png') }}" alt="Logo Pengayoman" class="kop-logo">
        <div class="kop-text">
            <h1>KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA</h1>
            <h2>KANTOR WILAYAH DIREKTORAT JENDERAL PEMASYARAKATAN NUSA TENGGARA BARAT</h2>
            <h3>LEMBAGA PEMASYARAKATAN KELAS IIA LOMBOK BARAT</h3>
            <p>JL. PRAMUKA, DUSUN PEMANGKET, DESA KURIPAN UTARA, KABUPATEN LOMBOK BARAT Telp 0370 631071 Fax 0370 631071</p>
        </div>
    </div>

    <div class="judul-surat">
        <h4>SURAT IZIN KUNJUNGAN</h4>
    </div>

    <table class="info-table">
        <tr>
            <td class="col-label">Nama Pengunjung</td>
            <td class="col-titik">:</td>
            <td style="text-transform: uppercase;">{{ $kunjungan->pengunjung->user->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="col-label">Jenis Kelamin</td>
            <td class="col-titik">:</td>
            <td>{{ $kunjungan->pengunjung->jenis_kelamin ?? '-' }}</td>
        </tr>
        <tr>
            <td class="col-label">KTP</td>
            <td class="col-titik">:</td>
            <td>{{ $kunjungan->pengunjung->no_ktp ?? '-' }}</td>
        </tr>
        <tr>
            <td class="col-label">Alamat</td>
            <td class="col-titik">:</td>
            <td style="text-transform: uppercase;">{{ $kunjungan->pengunjung->alamat ?? '-' }}</td>
        </tr>
        <tr>
            <td class="col-label">No. Telepon</td>
            <td class="col-titik">:</td>
            <td>{{ $kunjungan->pengunjung->no_hp ?? '-' }}</td>
        </tr>
        <tr>
            <td class="col-label">Pengikut</td>
            <td class="col-titik">:</td>
            <td>
                Laki-laki &nbsp;&nbsp;&nbsp;&nbsp;: {{ $kunjungan->pengikut_laki ?? 0 }} orang<br>
                Perempuan &nbsp;: {{ $kunjungan->pengikut_perempuan ?? 0 }} orang<br>
                Anak-anak &nbsp;&nbsp;: {{ $kunjungan->pengikut_anak ?? 0 }} orang<br>
                Jumlah &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: <strong>{{ $kunjungan->total_pengikut ?? 0 }} orang</strong>
            </td>
        </tr>
    </table>

    <div class="barang-title">Barang yang dititipkan :</div>
    <table class="barang-table">
        <thead>
            <tr>
                <th style="width: 5%;">No.</th>
                <th style="width: 65%;">Jenis Barang</th>
                <th style="width: 15%;">Jumlah</th>
                <th style="width: 15%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($kunjungan->barangBawaans as $index => $barang)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td>{{ $barang->jenis_barang }}</td>
                <td style="text-align: center;">{{ $barang->jumlah }}</td>
                <td>{{ $barang->keterangan ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td style="text-align: center;">-</td>
                <td>-</td>
                <td style="text-align: center;">-</td>
                <td>-</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="info-table">
        <tr>
            <td class="col-label" style="font-weight: bold;">Status : Narapidana</td>
            <td colspan="2" style="font-weight: bold;">Warga Binaan yang dikunjungi :</td>
        </tr>
        <tr>
            <td class="col-label"></td>
            <td class="col-titik">Nama</td>
            <td style="text-transform: uppercase;">: <strong>{{ $kunjungan->wbp->nama ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="col-label"></td>
            <td class="col-titik">Perkara</td>
            <td>: {{ $kunjungan->wbp->jenisKejahatan->nama_kejahatan ?? '-' }}</td>
        </tr>
        <tr>
            <td class="col-label"></td>
            <td class="col-titik">Blok / Kamar Hunian</td>
            <td style="text-transform: uppercase;">: BLOK {{ $kunjungan->wbp->sel->blok->nama_blok ?? '-' }} / {{ $kunjungan->wbp->sel->nama_sel ?? '-' }}</td>
        </tr>
    </table>

    <div class="footer-section">
        <div class="tanda-tangan">
            <div class="tanggal">Lombok Barat, {{ \Carbon\Carbon::parse($kunjungan->tanggal_kunjungan)->translatedFormat('d F Y') }}</div>
            <div style="margin-bottom: 5px;">PETUGAS LOKET</div>
            <div class="ttd-space"></div>
            <div class="nama-petugas">( ........................................ )</div>
        </div>
    </div>

    <div class="catatan">
        * Kunjungan Tidak Dipungut Biaya<br>
        * Apabila anda ada keluhan terhadap pelayanan kunjungan Silahkan SMS 08119102020
    </div>

</body>
</html>