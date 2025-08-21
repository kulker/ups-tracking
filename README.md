# 📦 UPS SOAP Entegrasyonu ( >PHP 7.4 )

Bu proje, **UPS Türkiye SOAP servisi** ile entegre çalışarak aşağıdaki iki metodu kullanır:

- `Login_V1` → Oturum açma ve `SessionID` alma  
- `GetLastTransactionByTrackingNumber_V1` → Gönderi takip numarasına göre son işlem bilgilerini alma  

---

---

## ⚙️ Kullanım

### Login & Takip Sorgusu

```php
use App\Libraries\UpsService;

$ups = new UpsService();

// UPS giriş bilgileri
$customerNumber = "...";
$username       = "...";
$password       = "....";

// Login ol ve SessionID al
$sessionID = $ups->login($customerNumber, $username, $password);

if ($sessionID) {
    // Takip numarası sorgula
    $trackingNumber = "1Z05576E6...";
    $result = $ups->getLastTransaction($sessionID, $trackingNumber);

    print_r($result);
} else {
    echo "Login başarısız!";
}
```
## 📤 Dönen Örnek Sonuç

```php
Array
(
    [StatusCode] => 2
    [ProcessDescription1] => DELIVERED
    [ProcessDescription2] => Teslim edildi
    [ProcessDescription_TR] => ALICIYA TESLİM EDİLDİ
)
```
