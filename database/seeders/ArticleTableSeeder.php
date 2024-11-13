<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\API\ArticleController;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Http\Request;


class ArticleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        Schema::disableForeignKeyConstraints();
        Article::truncate();
        Schema::enableForeignKeyConstraints();

        $articles = [
            [
                'article_title' => 'Kekuatan Berpikir Positif',
                'content' => "<p>Berpikir positif merupakan salah satu strategi psikologis yang dapat membantu seseorang 
                          menghadapi berbagai tantangan hidup dengan cara yang lebih optimis. Ketika seseorang 
                          mampu berpikir positif, ia cenderung melihat peluang di balik setiap kesulitan dan berfokus 
                          pada solusi daripada masalah. Hal ini dapat meningkatkan kesejahteraan mental dan 
                          emosional secara keseluruhan.</p>

                          <p>Penelitian menunjukkan bahwa individu yang memiliki pola pikir positif lebih cenderung 
                          memiliki tingkat stres yang lebih rendah. Mereka dapat menghadapi situasi sulit dengan lebih 
                          tenang dan rasional. Selain itu, berpikir positif juga dikaitkan dengan peningkatan kualitas 
                          tidur, produktivitas, dan hubungan interpersonal yang lebih baik.</p>

                          <p>Namun, berpikir positif bukan berarti mengabaikan masalah atau berusaha untuk selalu 
                          merasa bahagia. Ini lebih tentang bagaimana kita merespons tantangan dengan cara yang 
                          konstruktif dan tetap berusaha untuk menemukan sisi baik dalam setiap situasi. 
                          Dengan demikian, berpikir positif bisa membantu seseorang untuk memiliki pandangan hidup 
                          yang lebih seimbang dan realistis.</p>

                          <p>Dalam dunia psikologi, berpikir positif sering dikaitkan dengan teori self-fulfilling prophecy, 
                          di mana keyakinan seseorang dapat memengaruhi hasil yang terjadi. Ketika seseorang percaya 
                          bahwa mereka bisa sukses, mereka lebih mungkin bertindak dengan cara yang mendukung keberhasilan 
                          tersebut. Sebaliknya, jika seseorang selalu berpikir negatif, mereka mungkin tanpa sadar 
                          membatasi potensi mereka sendiri.</p>

                          <p>Oleh karena itu, mengembangkan kebiasaan berpikir positif dapat membawa dampak positif 
                          dalam jangka panjang. Ini tidak hanya membantu dalam menangani masalah sehari-hari, 
                          tetapi juga dapat meningkatkan kualitas hidup secara keseluruhan. Melalui latihan dan 
                          kesadaran, berpikir positif bisa menjadi alat yang ampuh dalam mencapai kesejahteraan mental 
                          dan emosional yang lebih baik.</p>",
                'publication_date' => '2024-10-15',
                'publisher_name' => 'Dr. Psikologi Indonesia',
                'admin_id' => 1, 
                'category_id' => 1,
                'article_img' => 'storage/article_photos/kecemasan_digital.jpg',
                // 'article_img' => new \Illuminate\Http\UploadedFile(storage_path('app/public/article_photos/kecemasan_digital.jpg'), 'kecemasan_digital.jpg'),
            ],
            [
                'article_title' => 'Pentingnya Keseimbangan Emosi dalam Kehidupan Sehari-hari',
                'content' => "<p>Keseimbangan emosi adalah kunci untuk menjalani kehidupan yang harmonis dan bahagia. 
                            Dalam dunia yang penuh dengan tekanan dan tuntutan, kemampuan untuk mengelola emosi dengan 
                            baik sangat penting agar seseorang dapat menghadapi tantangan tanpa terjebak dalam stres 
                            yang berlebihan.</p>

                            <p>Emosi yang tidak terkontrol bisa memicu berbagai masalah, baik dalam hubungan interpersonal 
                            maupun dalam pekerjaan. Seseorang yang mudah marah atau cemas cenderung membuat keputusan 
                            yang terburu-buru dan tidak rasional, yang pada akhirnya dapat merugikan diri sendiri dan 
                            orang lain. Oleh karena itu, belajar mengelola emosi menjadi sangat penting untuk menjaga 
                            kesehatan mental dan kesejahteraan sosial.</p>

                            <p><img src='https://example.com/images/emotional-balance.jpg' alt='Keseimbangan Emosi' style='max-width:100%;'></p>

                            <p>Psikologi modern menawarkan berbagai teknik untuk membantu individu mencapai keseimbangan 
                            emosi, seperti meditasi, mindfulness, dan terapi kognitif perilaku. Teknik-teknik ini 
                            dirancang untuk membantu seseorang mengenali, menerima, dan mengelola emosi mereka dengan 
                            cara yang sehat. Dengan praktik yang teratur, keseimbangan emosi dapat dicapai dan dipertahankan.</p>

                            <p>Selain itu, dukungan sosial juga memainkan peran penting dalam membantu seseorang 
                            menjaga keseimbangan emosional. Ketika seseorang memiliki jaringan dukungan yang kuat, 
                            mereka lebih mampu menghadapi stres dan tantangan hidup dengan lebih baik. Oleh karena itu, 
                            menjaga hubungan baik dengan orang-orang di sekitar kita juga merupakan bagian penting 
                            dari keseimbangan emosi.</p>",
                            
                'publication_date' => '2024-09-20',
                'publisher_name' => 'Dr. Emosi Sehat',
                'admin_id' => 1,
                'category_id' => 1,
                'article_img' => 'storage/article_photos/keseimbangan_emosi.jpg',
                // 'article_img' => new \Illuminate\Http\UploadedFile(storage_path('app/public/article_photos/keseimbangan_emosi.jpg'), 'keseimbangan_emosi.jpg'),
            ],
            [
                'article_title' => 'Mengatasi Kecemasan di Era Digital',
                'content' => "<p>Kecemasan adalah salah satu masalah kesehatan mental yang paling umum di era digital ini. 
                            Kehidupan yang serba cepat, tuntutan pekerjaan yang tinggi, dan tekanan sosial dari media 
                            sosial sering kali menjadi pemicu kecemasan bagi banyak orang. Tidak jarang, kecemasan 
                            tersebut berkembang menjadi gangguan kecemasan yang lebih serius.</p>

                            <p>Media sosial sering kali memperburuk kecemasan dengan memberikan tekanan untuk selalu 
                            tampil sempurna di hadapan publik. Seseorang mungkin merasa cemas ketika melihat kehidupan 
                            orang lain yang tampaknya lebih bahagia atau lebih sukses. Namun, penting untuk diingat 
                            bahwa apa yang kita lihat di media sosial sering kali bukan cerminan dari realitas.</p>

                            <p><img src='https://example.com/images/digital-anxiety.jpg' alt='Kecemasan di Era Digital' style='max-width:100%;'></p>

                            <p>Mengatasi kecemasan di era digital membutuhkan pendekatan yang holistik. Salah satu 
                            strategi yang efektif adalah dengan membatasi waktu yang dihabiskan di media sosial dan 
                            lebih fokus pada kegiatan yang mendukung kesehatan mental, seperti olahraga, meditasi, 
                            dan interaksi sosial di dunia nyata. Selain itu, mencari bantuan profesional jika kecemasan 
                            mulai mengganggu kehidupan sehari-hari juga merupakan langkah yang sangat dianjurkan.</p>

                            <p>Dalam banyak kasus, terapi kognitif perilaku dan terapi berbasis mindfulness telah terbukti 
                            efektif dalam membantu individu mengatasi kecemasan. Dengan dukungan yang tepat, seseorang 
                            dapat belajar untuk mengelola kecemasan mereka dan menjalani kehidupan yang lebih tenang 
                            dan seimbang.</p>",
                            
                'publication_date' => '2024-08-10',
                'publisher_name' => 'Dr. Mental Sehat',
                'admin_id' => 1, 
                'category_id' => 2,
                'article_img' => 'storage/article_photos/kecemasan_digital.jpg',
                // 'article_img' => new \Illuminate\Http\UploadedFile(storage_path('app/public/article_photos/kecemasan_digital.jpg'), 'kecemasan_digital.jpg'),
            ],
        ];

        for ($i=0; $i < 10; $i++) { 
            foreach ($articles as $article) {
                Article::create($article);
            }
        }
        
    }
}
