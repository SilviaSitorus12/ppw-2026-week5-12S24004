<?php

namespace App\Http\Controllers\Soap;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use SimpleXMLElement;
use Throwable;

/**
 * Simulasi ringkas layanan SOAP "Bebas Pustaka" untuk Sistem Yudisium (legacy).
 * Tanpa ext-soap: parsing memakai SimpleXML + XPath local-name().
 *
 * GET  /soap/sipus.wsdl -> kontrak WSDL operasi CheckClearanceStatus
 * POST /soap/sipus      -> memproses <soap:Envelope>
 */
class LibrarySoapController extends Controller
{
    private const SERVICE_NS     = 'http://sipus.del.ac.id/soap/sipus';
    private const SOAP_ENV_NS    = 'http://schemas.xmlsoap.org/soap/envelope/';
    private const MAX_NIM_LENGTH = 20;

    /**
     * 1. Kontrak WSDL (types, message, portType, binding, service).
     */
    public function wsdl(): Response
    {
        $ns       = self::SERVICE_NS;
        $endpoint = htmlspecialchars(url('/soap/sipus'), ENT_XML1, 'UTF-8');

        $wsdl = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<wsdl:definitions name="SipusSoapService"
    targetNamespace="{$ns}"
    xmlns:tns="{$ns}"
    xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
    xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema">

    <!-- 0. Types: skema XSD untuk elemen request & response -->
    <wsdl:types>
        <xsd:schema targetNamespace="{$ns}" elementFormDefault="qualified">
            <xsd:element name="CheckClearanceStatusRequest">
                <xsd:complexType>
                    <xsd:sequence>
                        <xsd:element name="nim_peminjam" type="xsd:string"/>
                    </xsd:sequence>
                </xsd:complexType>
            </xsd:element>
            <xsd:element name="CheckClearanceStatusResponse">
                <xsd:complexType>
                    <xsd:sequence>
                        <xsd:element name="nim_peminjam" type="xsd:string"/>
                        <xsd:element name="active_loans" type="xsd:int"/>
                        <xsd:element name="is_cleared" type="xsd:boolean"/>
                    </xsd:sequence>
                </xsd:complexType>
            </xsd:element>
        </xsd:schema>
    </wsdl:types>

    <!-- 1. Message: parameter input & output -->
    <wsdl:message name="CheckClearanceStatusInput">
        <wsdl:part name="parameters" element="tns:CheckClearanceStatusRequest"/>
    </wsdl:message>
    <wsdl:message name="CheckClearanceStatusOutput">
        <wsdl:part name="parameters" element="tns:CheckClearanceStatusResponse"/>
    </wsdl:message>

    <!-- 2. PortType: daftar operasi -->
    <wsdl:portType name="SipusClearancePortType">
        <wsdl:operation name="CheckClearanceStatus">
            <wsdl:input message="tns:CheckClearanceStatusInput"/>
            <wsdl:output message="tns:CheckClearanceStatusOutput"/>
        </wsdl:operation>
    </wsdl:portType>

    <!-- 3. Binding: SOAP 1.1 over HTTP, document/literal -->
    <wsdl:binding name="SipusClearanceSoapBinding" type="tns:SipusClearancePortType">
        <soap:binding style="document" transport="http://schemas.xmlsoap.org/soap/http"/>
        <wsdl:operation name="CheckClearanceStatus">
            <soap:operation soapAction="{$ns}#CheckClearanceStatus" style="document"/>
            <wsdl:input><soap:body use="literal"/></wsdl:input>
            <wsdl:output><soap:body use="literal"/></wsdl:output>
        </wsdl:operation>
    </wsdl:binding>

    <!-- 4. Service: alamat fisik endpoint -->
    <wsdl:service name="SipusSoapService">
        <wsdl:documentation>Cek status bebas pustaka mahasiswa (SIPUS-Del) untuk Sistem Yudisium.</wsdl:documentation>
        <wsdl:port name="SipusClearancePort" binding="tns:SipusClearanceSoapBinding">
            <soap:address location="{$endpoint}"/>
        </wsdl:port>
    </wsdl:service>
</wsdl:definitions>
XML;

        return response($wsdl, 200)->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     *
     * 2. Memproses <soap:Envelope> berisi <nim_peminjam>.
     */
    public function handle(Request $request): Response
    {
        $rawXml = trim($request->getContent());

        if ($rawXml === '') {
            return $this->soapFault('soap:Client', 'Body XML SOAP tidak boleh kosong.');
        }

        // Parsing aman: error libxml ditangkap internal, LIBXML_NONET mencegah akses jaringan.
        // (Sengaja TIDAK memakai LIBXML_NOENT agar entitas eksternal/XXE tidak di-expand.)
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rawXml, SimpleXMLElement::class, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            return $this->soapFault('soap:Client', 'Format XML tidak valid (Malformed XML).');
        }

        // Elemen akar wajib <Envelope> dengan namespace SOAP 1.1
        $envelope = $xml->xpath(
            '/*[local-name()="Envelope" and namespace-uri()="' . self::SOAP_ENV_NS . '"]'
        );
        if (empty($envelope)) {
            return $this->soapFault(
                'soap:Client',
                'Elemen akar harus <soap:Envelope> dengan namespace ' . self::SOAP_ENV_NS . '.'
            );
        }

        // Ambil <nim_peminjam> di dalam Body/CheckClearanceStatusRequest, bebas prefix namespace
        $nimNodes = $xml->xpath(
            '/*[local-name()="Envelope"]/*[local-name()="Body"]'
            . '/*[local-name()="CheckClearanceStatusRequest"]/*[local-name()="nim_peminjam"]'
        );

        if (empty($nimNodes)) {
            return $this->soapFault(
                'soap:Client',
                'Operasi tidak dikenali atau elemen <nim_peminjam> tidak ditemukan pada CheckClearanceStatusRequest.'
            );
        }

        $nim = trim((string) $nimNodes[0]);

        if ($nim === '') {
            return $this->soapFault('soap:Client', 'Elemen <nim_peminjam> tidak boleh kosong.');
        }

        if (mb_strlen($nim) > self::MAX_NIM_LENGTH) {
            return $this->soapFault(
                'soap:Client',
                'Panjang <nim_peminjam> maksimal ' . self::MAX_NIM_LENGTH . ' karakter.'
            );
        }

        try {
            // Hitung pinjaman aktif dari DB nyata (tabel Minggu 6)
            $activeLoans = DB::table('peminjamans')
                ->where('nim_peminjam', $nim)
                ->where('status', 'dipinjam')
                ->count();
        } catch (Throwable $e) {
            report($e);
            return $this->soapFault('soap:Server', 'Terjadi kesalahan internal saat membaca data peminjaman.');
        }

        $ns        = self::SERVICE_NS;
        $safeNim   = htmlspecialchars($nim, ENT_XML1, 'UTF-8');
        $isCleared = $activeLoans === 0 ? 'true' : 'false';

        $responseXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
               xmlns:m="{$ns}">
    <soap:Header>
        <m:ProviderInfo>SIPUS-Del Legacy SOAP Engine v1.0</m:ProviderInfo>
    </soap:Header>
    <soap:Body>
        <m:CheckClearanceStatusResponse>
            <m:nim_peminjam>{$safeNim}</m:nim_peminjam>
            <m:active_loans>{$activeLoans}</m:active_loans>
            <m:is_cleared>{$isCleared}</m:is_cleared>
        </m:CheckClearanceStatusResponse>
    </soap:Body>
</soap:Envelope>
XML;

        return response($responseXml, 200)->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * Pembentuk <soap:Fault> SOAP 1.1 (HTTP 500 sesuai spesifikasi SOAP 1.1 binding).
     */
    private function soapFault(string $faultCode, string $faultString): Response
    {
        $safeCode = htmlspecialchars($faultCode, ENT_XML1, 'UTF-8');
        $safeMsg  = htmlspecialchars($faultString, ENT_XML1, 'UTF-8');

        $faultXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <soap:Fault>
            <faultcode>{$safeCode}</faultcode>
            <faultstring>{$safeMsg}</faultstring>
        </soap:Fault>
    </soap:Body>
</soap:Envelope>
XML;

        return response($faultXml, 500)->header('Content-Type', 'text/xml; charset=utf-8');
    }
}