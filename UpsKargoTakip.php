<?php

use SoapClient;
use SoapFault;

class UpsKargoTakip
{
    private $client;
    private $username;
    private $password;

    public function __construct()
    {
        // UPS SOAP servis URL
        $wsdl = "https://ws.ups.com.tr/QueryPackageInfo/wsQueryPackagesInfo.asmx?WSDL";

        try {
            // SOAP Client oluştur
            $this->client = new SoapClient($wsdl, [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'soap_version' => SOAP_1_1
            ]);
        } catch (SoapFault $e) {
            throw new \Exception("SOAP Client başlatılamadı: " . $e->getMessage());
        }
    }

    /**
     * UPS servisine login olur ve sessionID döner
     */
    public function login(string $customerNumber, string $userName, string $password): ?string
    {
        try {
            $params = [
                "CustomerNumber" => $customerNumber,
                "UserName" => $userName,
                "Password" => $password
            ];

            $response = $this->client->Login_V1($params);

            if (isset($response->Login_V1Result->SessionID)) {
                $this->username = $userName;
                $this->password = $password;
                return $response->Login_V1Result->SessionID;
            }

            return null;
        } catch (SoapFault $e) {
            throw new \Exception("Login hatası: " . $e->getMessage());
        }
    }

    /**
     * Takip numarasına göre son işlem bilgisini getirir
     * Dönen alanlar: StatusCode, ProcessDescription1, ProcessDescription2, ProcessDescription_TR
     */
    public function getLastTransaction(string $sessionID, string $trackingNumber): ?array
    {
        try {
            $params = [
                "SessionID" => $sessionID,
                "InformationLevel" => "1",
                "TrackingNumber" => $trackingNumber
            ];

            $response = $this->client->GetLastTransactionByTrackingNumber_V1($params);

            if (!isset($response->GetLastTransactionByTrackingNumber_V1Result)) {
                return null;
            }

            $result = (array)$response->GetLastTransactionByTrackingNumber_V1Result;
            if (!isset($result['PackageTransaction'])) {
                return null;
            }

            $result = (array)$result['PackageTransaction']; // Dönüş tipini array'e çeviriyoruz
            // Debugging purpose

            // print_r($result); // Bu satırı kaldırabilirsiniz, sadece hata ayıklama için eklendi

            // StatusCode Türkçe açıklama tablosu
            $statusMap = [
                1  => "GİRİŞ SCAN EDİLDİ",
                2  => "ALICIYA TESLİM EDİLDİ",
                3  => "ÖZEL DURUM OLUŞTU",
                4  => "KURYE DAĞITMAK ÜZERE ÇIKARDI",
                5  => "KURYE GERİ GETİRDİ",
                6  => "ŞUBEYE GÖNDERİLDİ",
                7  => "ŞUBEDEN GELDİ",
                12 => "K. KONTEYNERE KONDU",
                15 => "MANİFESTO FAZLASI",
                16 => "K. KONTEYNERDEN ÇIKTI",
                17 => "GÖNDERENE İADE AMAÇLI ÇIKIŞ",
                18 => "MÜŞTERİ TOPLU GİRİŞ",
                19 => "ŞUBEDE BEKLEYEN",
                30 => "KONSOLOSLUKTAN TESLİM ALINDI",
                31 => "ÇAĞRI SONUCU ALINDI",
                32 => "DEPOYA GİRDİ",
                33 => "DEPODAN ÇIKTI",
                34 => "EDI BİLGİ TRANSFER",
                35 => "MÜŞTERİ DEPODA OKUNDU",
                36 => "TOPLU DAĞITIMA ÇIKIŞ",
                37 => "TRANSİT KARŞILAMA",
                38 => "TRANSİT ÇIKIŞ"
            ];

            $statusCode = $result['StatusCode'] ?? '';
            $processDesc1 = $result['ProcessDescription1'] ?? '';
            $processDesc2 = $result['ProcessDescription2'] ?? '';

            if (isset($result['ProcessTimeStamp']) && !empty($result['ProcessTimeStamp'])) {
                $dateObj = \DateTime::createFromFormat("Ymd-His", $result['ProcessTimeStamp']);
                // Türkçe tarih formatı (gün.ay.yıl saat:dakika:saniye)
                setlocale(LC_TIME, 'tr_TR.UTF-8');
                $fmt = new \IntlDateFormatter(
                    'tr_TR',
                    \IntlDateFormatter::LONG,
                    \IntlDateFormatter::MEDIUM,
                    'Europe/Istanbul',
                    \IntlDateFormatter::GREGORIAN
                );
                $formatted =  $fmt->format($dateObj);
                $processDesc2 .= ', Son İşlem Tarihi: ' . $formatted;
            }


            return [
                "StatusCode"           => $statusCode,
                "ProcessDescription1"  => $processDesc1,
                "ProcessDescription2"  => $processDesc2,
                "ProcessDescription_TR" => $statusMap[$statusCode] ?? Null
            ];
        } catch (SoapFault $e) {
            throw new \Exception("Takip sorgusu hatası: " . $e->getMessage());
        }
    }
}
