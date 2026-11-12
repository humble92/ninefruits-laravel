# Yedekleme ve geri yükleme

- Hedef kitle: BT operatörleri

## Neyin yedekleneceği

| Hedef | Yöntem | Sıklık (önerilen) |
|---|---|---|
| PostgreSQL | `pg_dump -Fc` veya PITR (WAL arşivleme) | Günlük tam yedek + sürekli WAL |
| Dosya depolama (`storage/app/private` veya S3) | Anlık görüntü veya sürümlü bucket | DB ile aynı |
| Ortam yapılandırması ve anahtarlar | Gizli bilgi deposu | Her değişiklikte |

DB ve dosya depolama aynı zaman noktasına geri yüklenebilmelidir (fotoğrafların ve belge PDF'lerinin meta verileri DB'de tutulur).

## DB yedekleme örneği

```bash
pg_dump -U app_migrator -d sis -Fc -f sis-$(date +%F).dump
```

`app_migrator` tüm şemaların (`public`, `audit`, `reporting`, `util`) sahibidir; bu nedenle yedeklemeyi bu hesapla alın. Roller (`app_*`) küme nesneleridir ve dökümün parçası değildir. Geri yükleme sunucusunda, önce [Kurulum](installation.md) belgesinin 2. bölümünde anlatıldığı gibi rolleri oluşturun.

## Geri yükleme örneği

```bash
createdb -U postgres sis_restore
psql -U postgres -d sis_restore -f database/sql/roles/prepare_database.sql
pg_restore -U app_migrator -d sis_restore --no-owner --role=app_migrator sis-2026-10-22.dump
```

## Geri yükleme tatbikatları

Her çeyrekte ayrı bir sunucuya geri yükleme yapın ve şunları doğrulayın: oturum açma, kişisel verilerin şifresinin çözülmesi (anahtarlar eşleşiyor), denetim kayıtlarının görüntülenmesi, dosyaların açılması (fotoğraflar, PDF'ler).
