<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
// use App\Models\ProdusenData;

class ProdusenDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['produsen_id'=>2,'nama_produsen'=>'Badan Pusat Statistik Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>10,'nama_produsen'=>'Dinas Kesehatan Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>19,'nama_produsen'=>'Dinas Ketahanan Pangan, Kelautan dan Perikanan Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>13,'nama_produsen'=>'Dinas Tenaga Kerja Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>22,'nama_produsen'=>'Dinas Sosial Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>16,'nama_produsen'=>'Dinas Pemberdayaan Masyarakat Dan Desa','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>21,'nama_produsen'=>'Dinas Kependudukan dan Pencatatan Sipil Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>18,'nama_produsen'=>'Dinas Perpustakaan dan Kearsipan Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>3,'nama_produsen'=>'Dinas Kebudayaan Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>9,'nama_produsen'=>'Dinas Pertanian Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>6,'nama_produsen'=>'Dinas Perindustrian dan Perdagangan Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>4,'nama_produsen'=>'Dinas Pariwisata Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>27,'nama_produsen'=>'Badan Kesatuan Bangsa dan Politik Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>24,'nama_produsen'=>'Dinas Perhubungan Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>34,'nama_produsen'=>'Bagian Tata Pemerintahan dan Kerjasama Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>15,'nama_produsen'=>'Dinas Penanaman Modal dan Pelayanan Terpadu Satu Pintu Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>66,'nama_produsen'=>'Telkom Indonesia','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>14,'nama_produsen'=>'Dinas Komunikasi dan Informatika Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>68,'nama_produsen'=>'PT Pos cabang Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>17,'nama_produsen'=>'Dinas Perumahan, Kawasan Permukiman, Dan Pertanahan','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>8,'nama_produsen'=>'Dinas Lingkungan Hidup Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>7,'nama_produsen'=>'Dinas Pendidikan Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>5,'nama_produsen'=>'Dinas Koperasi dan UKM Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>20,'nama_produsen'=>'Dinas Pekerjaan Umum dan Penataan Ruang Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>12,'nama_produsen'=>'Dinas Pemberdayaan Perempuan dan Perlindungan Anak, Pengendalian Penduduk dan Keluarga Berencana','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>29,'nama_produsen'=>'Badan Pertanahan Nasional','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>23,'nama_produsen'=>'Dinas Kepemudaan dan Olah Raga Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>67,'nama_produsen'=>'Perusahaan Listrik Negara','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>61,'nama_produsen'=>'Istana Tampaksiring','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>11,'nama_produsen'=>'Satuan Polisi Pamong Praja Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>28,'nama_produsen'=>'Badan Penanggulangan Bencana Daerah Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>26,'nama_produsen'=>'Badan Kepegawaian dan Pengembangan SDM Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>58,'nama_produsen'=>'Polres Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>74,'nama_produsen'=>'PDAM','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>62,'nama_produsen'=>'Kantor Kementerian Agama','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>47,'nama_produsen'=>'Sekretariat Dewan DPRD Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>57,'nama_produsen'=>'Kodim 1616 Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>60,'nama_produsen'=>'Pengadilan Negeri','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>65,'nama_produsen'=>'Rumah Tahanan Negara','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>64,'nama_produsen'=>'Gudang Bulog Batubulan','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>56,'nama_produsen'=>'Kodam IX Udayana','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>69,'nama_produsen'=>'BPD Bali Cabang Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>71,'nama_produsen'=>'BRI Cabang Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>63,'nama_produsen'=>'KPP Pratama Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>72,'nama_produsen'=>'BRI Cabang Ubud','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>73,'nama_produsen'=>'Yonzipur','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>81,'nama_produsen'=>'Komisi Pemilihan Umum Daerah','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>25,'nama_produsen'=>'Badan Pengelolaan Keuangan dan Aset Daerah Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>1,'nama_produsen'=>'Badan Perencanaan Pembangunan Daerah dan Penelitian Pengembangan Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>82,'nama_produsen'=>'Badan Narkotika Nasional Kabupaten Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>70,'nama_produsen'=>'BPD Bali Cabang Ubud','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>78,'nama_produsen'=>'Majelis Madia Desa Pekraman Kab Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>59,'nama_produsen'=>'Kejaksaan Negeri','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>76,'nama_produsen'=>'RSU Sanjiwani','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>79,'nama_produsen'=>'Dinas Pendapatan','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>30,'nama_produsen'=>'Bagian Hukum Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>75,'nama_produsen'=>'KONI','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>80,'nama_produsen'=>'LP LPD K (Lembaga Pemberdayaan LPD Kabupaten Gianyar)','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>84,'nama_produsen'=>'Badan Riset dan Inovasi Daerah Kabupaten Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>49,'nama_produsen'=>'Kecamatan Blahbatuh','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>48,'nama_produsen'=>'Kecamatan Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>50,'nama_produsen'=>'Kecamatan Sukawati','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>53,'nama_produsen'=>'Kecamatan Ubud','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>51,'nama_produsen'=>'Kecamatan Tampaksiring','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>52,'nama_produsen'=>'Kecamatan Tegallalang','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>54,'nama_produsen'=>'Kecamatan Payangan','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>32,'nama_produsen'=>'Bagian Kesejahtraan Rakyat Kab. Gianyar','kontak'=>null,'alamat'=>null],
            ['produsen_id'=>85,'nama_produsen'=>'Rumah Sakit Umum Payangan','kontak'=>null,'alamat'=>null],
        ];

        foreach ($data as $row) {
            DB::table('produsen_data')->insert([
                'produsen_id'=>$row['produsen_id'],
                'nama_produsen'=>$row['nama_produsen'],
                'kontak'=>$row['kontak'],
                'alamat'=>$row['alamat'],
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);
        }
    }
}