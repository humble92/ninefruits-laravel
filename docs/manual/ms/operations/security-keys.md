# Pengurusan kunci

- Pembaca: pengendali IT

| Kunci | Tujuan | Jika hilang | Penggiliran |
|---|---|---|---|
| `APP_KEY` | Sesi, kuki, rahsia pengesahan dua faktor, tandatangan kuki peranti yang diingati | Setiap pengguna dilog keluar; pengesahan dua faktor yang telah didaftarkan (aplikasi pengesah) berhenti berfungsi | Letakkan kunci sebelumnya dalam `APP_PREVIOUS_KEYS`, kemudian gilirkan |
| `PII_ENCRYPTION_KEY` | Penyulitan data peribadi seperti nombor kad pengenalan | **Data peribadi yang disulitkan tidak dapat dipulihkan** | Prosedur di bawah |
| `PII_HASH_KEY` | Hash untuk carian padanan tepat data peribadi | Carian berhenti berfungsi (data kekal) | Pada dasarnya tidak digilirkan |

Prinsip penyimpanan

- Simpan sekurang-kurangnya dua salinan ketiga-tiga kunci di luar konfigurasi persekitaran pelayan, dalam stor rahsia (Vault, pengurus rahsia awan, dsb.) atau dalam storan luar talian yang dimeterai.
- Jangan sekali-kali menyimpannya bersama fail sandaran. Walaupun sandaran pangkalan data bocor, data peribadi mesti kekal tidak boleh dibaca tanpa kunci.

## Prosedur penggiliran kunci data peribadi

1. Jana kunci baharu dengan `php artisan pii:generate-key` (gunakan hanya `PII_ENCRYPTION_KEY` daripada output).
2. Letakkan kunci semasa dalam `PII_PREVIOUS_KEYS` (dipisahkan dengan koma jika lebih daripada satu) dan kunci baharu dalam `PII_ENCRYPTION_KEY`.
3. Muat semula konfigurasi: `php artisan config:cache`, `php artisan horizon:terminate`.
4. Bagi setiap model data peribadi, semak bilangan rekod yang terjejas dengan `php artisan pii:reencrypt {model} --dry-run`, kemudian jalankannya tanpa `--force`.
5. Setelah Horizon menunjukkan kelompok selesai dan `--dry-run` melaporkan 0, buang kunci sebelumnya daripada `PII_PREVIOUS_KEYS`.
6. Simpan kunci sebelumnya dalam keadaan dimeterai sepanjang tempoh simpanan sandaran (ia diperlukan untuk memulihkan sandaran yang lebih lama).
