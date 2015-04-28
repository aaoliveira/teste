<?php

namespace App;

use NFePHP\Common\Dom\Dom;
use NFePHP\Common\DateTime\DateTime;

class Dados
{
    public static $nCanc = 0;
    
    public static function extrai($aList, $cnpj = '')
    {
        $aResp = array();
        $totFat = 0;
        $totPeso = 0;
        $totIcms = 0;
        foreach ($aList as $file) {
            $dom = null;
            $ide = null;
            $emit = null;
            $dest = null;
            $dom = new Dom();
            $dom->loadXMLFile($file);
            $ide = $dom->getNode('ide');
            $emit = $dom->getNode('emit');
            $dest = $dom->getNode('dest');
            $icmsTot = $dom->getNode('ICMSTot');
            $vol = $dom->getNode('vol');
            $cStat = $dom->getNodeValue('cStat');
            if ($cStat != '100') {
                self::$nCanc++;
            }
            $dhEmi = $dom->getValue($ide, 'dhEmi');
            if (empty($dhEmi)) {
                $dhEmi = $dom->getValue($ide, 'dEmi');
            }
            $tsEmi = DateTime::convertSefazTimeToTimestamp($dhEmi);
            $data = date('d/m/Y', $tsEmi);
            $emitCNPJ = $dom->getValue($emit, 'CNPJ');
            $emitRazao = $dom->getValue($emit, 'xNome');
            $destRazao = $dom->getValue($dest, 'xNome');
            $vNF = $dom->getValue($icmsTot, 'vNF');
            $vNFtext = 'R$ '.number_format($vNF, '2', ',', '.');
            $serie = $dom->getNodeValue('serie');
            $nProt = $dom->getNodeValue('nProt');
            $nome = $emitRazao;
            if ($emitCNPJ == $cnpj) {
                $nome = $destRazao;
            }
            $email = $dom->getValue($dest, 'email');
            $aObscont = $dom->getElementsByTagName('obsCont');
            if (count($aObscont) > 0) {
                foreach ($aObscont as $obsCont) {
                    $xCampo = $obsCont->getAttribute('xCampo');
                    if ($xCampo == 'email') {
                        $email .= ";" . $dom->getValue($obsCont, 'xTexto');
                    }
                }
            }
            if (substr($email, 0, 1) == ';') {
                $email = substr($email, 1, strlen($email)-1);
            }
            $vICMS = $dom->getValue($icmsTot, 'vICMS');
            $totIcms += $vICMS;
            $valorFat = 0;
            if ($vICMS != 0 && $cStat == '100') {
                $valorFat = $vNF;
            }
            $totFat += $valorFat;
            $pesoL = $dom->getValue($vol, 'pesoL');
            if ($pesoL != '') {
                $totPeso += $pesoL;
            }
            
            $aResp[] = array(
                'nNF' => $dom->getValue($ide, 'nNF'),
                'serie' => $serie,
                'data' =>  $data,
                'nome' => $nome,
                'natureza' => $dom->getValue($ide, 'natOp'),
                'cStat' => $cStat,
                'vNF' => $vNFtext,
                'nProt' => $nProt,
                'email' => $email
            );
        }
        return array(
            'totFat' => $totFat,
            'totPeso' => $totPeso,
            'totIcms' => $totIcms,
            'aNF' => $aResp
        );
    }
}
