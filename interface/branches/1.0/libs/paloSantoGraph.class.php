<?php
/* vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
  Codificación: UTF-8
  +----------------------------------------------------------------------+
  | Elastix version 0.5                                                  |
  | http://www.elastix.org                                               |
  +----------------------------------------------------------------------+
  | Copyright (c) 2006 Palosanto Solutions S. A.                         |
  +----------------------------------------------------------------------+
  | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
  | Telfs. 2283-268, 2294-440, 2284-356                                  |
  | Guayaquil - Ecuador                                                  |
  | http://www.palosanto.com                                             |
  +----------------------------------------------------------------------+
  | The contents of this file are subject to the General Public License  |
  | (GPL) Version 2 (the "License"); you may not use this file except in |
  | compliance with the License. You may obtain a copy of the License at |
  | http://www.opensource.org/licenses/gpl-license.php                   |
  |                                                                      |
  | Software distributed under the License is distributed on an "AS IS"  |
  | basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See  |
  | the License for the specific language governing rights and           |
  | limitations under the License.                                       |
  +----------------------------------------------------------------------+
  | The Original Code is: Elastix Open Source.                           |
  | The Initial Developer of the Original Code is PaloSanto Solutions    |
  +----------------------------------------------------------------------+
  $Id: paloSantoGraph.class.php,v 1.1.1.1 2007/07/06 21:31:55 gcarrillo Exp $ */

class PaloGraph {

    var $image;
    var $arr_colores;
    var $arrPaleta;
    var $lienzoAncho;
    var $lienzoAltura;
    var $anchoCuadricula;
    var $bordeInternoIzq;
    var $_timestampInicioIntervalo;
    var $_timestampInicioSiguienteIntervalo;
    var $_duracionIntervaloSegundos;
    var $_duracionGraficoSegundos;
    var $arrGrilla;
    var $debug;

    function PaloGraph($ancho, $altura, $bgColorHEX="")
    {

        $this->lienzoAncho  = $ancho;
        $this->lienzoAltura = $altura;
        $this->debug = false;        
        //$this->debug = true;

        $this->image        = imagecreate($this->lienzoAncho, $this->lienzoAltura);
        if($bgColorHEX=="" or !$this->_esColorHex($bgColorHEX)) {
            $bgColorHEX = "FFFFFF";
        }
        $this->arrPaleta = array($bgColorHEX => $this->_imageColorAllocateHEX($bgColorHEX));
        imagefill($this->image, $this->lienzoAncho, $this->lienzoAltura, $this->arrPaleta[$bgColorHEX]);
    }    

    // 
    function debug()
    {
        $this->debug = true;
    }

    // hay que pasarle ancho, alto, x, y
    function crearPie3D($arrValores, $arrColores) // seria bueno pasar el ultimo parametro por defecto y tener un arreglo por defecto
     {
        // tengo que verificar que $arr_valores contenga un arreglo

        $arrColoresCla   = array();
        $arrColoresOsc   = array();

        $contador=0;
        foreach($arrColores as $v) {
            // NOTA IMPORTANTE: Supongo que el color hexadecimal, es decir $v no va a contener el caracter de #... mejor dicho
            // ya voy a quitar el soporte de este caracter...
            $arrColoresCla[$contador] = $v;
            $colorOscurecido = $this->_oscureceColor($v, 0.2);
            $arrColoresOsc[$contador]= $colorOscurecido;
            if(!$this->_estaColorEnPaleta($v)) {
                $this->arrPaleta[$v] = $this->_imageColorAllocateHEX($v);
            }
            if(!$this->_estaColorEnPaleta($colorOscurecido)) {
                $this->arrPaleta[$colorOscurecido] = $this->_imageColorAllocateHEX($colorOscurecido);
            }
            $contador++;
        }

        for($y=60; $y>50; $y--) {
            $this->_dibujaPie($arrValores, 60, $y, 100, 50, $arrColoresOsc);
        }
        $this->_dibujaPie($arrValores, 60, 50, 100, 50, $arrColoresCla);
        // me gustaria aqui al final desalocar colores... no se si se pueda antes de dibujar la imagen final... mmm
    }

    function crearBarra($progreso, $x=6, $y=6, $altura=5, $ancho=16, $color_relleno="FF6600", $color_borde="333333", $tresD="true")
    {
        // ta verde aun la funcion crearBarra, tengo que terminarla de implementar...
        if(!$this->_esColorHEX($color_borde)) {
            $color_borde = "333333";
        }

        if(!$this->_esColorHEX($color_relleno)) {
            $color_relleno = "FF6600";
        }

        // valores por defecto
        if(!isset($progreso) or ($progreso<0) or ($progreso > 1)) $progreso = 0;
        if(!isset($altura) or ($altura<0)) $altura = 5;
        if(!isset($ancho) or ($ancho<0)) $ancho = 16;
        if(!isset($x) or ($x<0)) $x = 6;
        if(!isset($y) or ($y<0)) $y = 6;

        if(!$this->_estaColorEnPaleta($color_borde)) {
            $this->arrPaleta[$color_borde]   = $this->_imageColorAllocateHEX($color_borde);
        }

        if(!$this->_estaColorEnPaleta($color_relleno)) {
            $this->arrPaleta[$color_relleno] = $this->_imageColorAllocateHEX($color_relleno);
        }

        /* dibujo el borde de la barra de progreso */
    
        // el espesor de la barra de progreso es el definido por la variable espesor_barra
    
        $barra_xizq = $x;
        $barra_xder = $x+$ancho;
        $barra_ysup = $y;
        $barra_yinf = $y+$altura;
    
        imageline ($this->image, $barra_xizq, $barra_ysup, $barra_xizq, $barra_yinf, $this->arrPaleta[$color_borde]); // marco izq.
        imageline ($this->image, $barra_xder, $barra_ysup, $barra_xder, $barra_yinf, $this->arrPaleta[$color_borde]); // marco der.
        imageline ($this->image, $barra_xizq, $barra_ysup, $barra_xder, $barra_ysup, $this->arrPaleta[$color_borde]); // marco sup.
        imageline ($this->image, $barra_xizq, $barra_yinf, $barra_xder, $barra_yinf, $this->arrPaleta[$color_borde]); // marco inf.

        /* dibujo la barra de progreso */
    
        // los limites para dicha barra son
    
        $prog_xizq = $barra_xizq + 1; // le aumento un pixel para que no dibuje encima del borde
        $prog_xder = $barra_xder - 1; // le disminuyo un pixel para que no dibuje encima del borde
        $prog_ysup = $barra_ysup + 1; // le aumento un pixel para que no dibuje encima del borde
        $prog_yinf = $barra_yinf - 1; // le disminuyo un pixel para que no dibuje encima del borde
    
        $ancho_barra_progreso = $prog_xder - $prog_xizq;
    
        $prog_xprogress = round((($progreso/1) * $ancho_barra_progreso) + $prog_xizq);
    
        if($tresD=="false") {
            imagefilledrectangle ($this->image, $prog_xizq, $prog_ysup, $prog_xprogress, $prog_yinf, $this->arrPaleta[$color_relleno]);
        } else {
            // Me barro cada pixel X y dibujo una linea vertical.
            // Hay un total de $prog_yinf-$prog_ysup pixeles de alto
            $pixeles_alto=$prog_yinf-$prog_ysup;
            if($pixeles_alto>0) {
                $pasoOscurecimiento=(25/$pixeles_alto)/100;
            } 

            for($pY=$prog_ysup, $oscurecimiento=0; $pY<=($prog_yinf-$prog_ysup+1); $pY++, $oscurecimiento=$oscurecimiento+$pasoOscurecimiento) {
                $nuevoColorLinea=$this->_oscureceColor($color_relleno, $oscurecimiento);
                imageline($this->image, $prog_xizq, $pY, $prog_xprogress, $pY, $this->_imageColorAllocateHEX($nuevoColorLinea));
            }
        }
    }

    function genSalida()
    {
        $this->_tareasFinales();
        if(!$this->debug) {
            header("Content-type: image/png");
            imagepng($this->image); 
        }
        imagedestroy($this->image);
    }

    function _tareasFinales() 
    {
        // Esta funcion se deja vacia para que luego pueda ser redefinida en clases extendidas
    }

    // A CONTINUACION FUNCIONES PRIVADAS

    function _dibujaPie($arr_valores, $cx, $cy, $w, $h, $arr_colores)
    {   
        $suma_valores = array_sum($arr_valores);
        $totalcolores = sizeof($arr_colores);
        $totalvalores = sizeof($arr_valores);
        $grado_fin = 0;
        $numcolor=0;
        $numvalor=0;
        foreach($arr_valores as $i => $v) { 
            if($numcolor>=$totalcolores) $numcolor=0;
            // A continuacion considero el caso en el q el numero de valoreses n*$totalcolores+1 donde n es un entero
            // en este caso el color del ultimo arco no puede ser igual al color del primer arco porque se confundirian
            // NOTA: TENGO QUE CONSIDERAR EL CASO PARTICULAR DE CUANDO TENGO 3 COLORES O MENOS!!!...
            if($numcolor==0 and $numvalor==($totalvalores-1)) $numcolor++;
            // aqui me barro el arreglo arr_valores y obtengo las cantidades almacenadas y obtengo su
            // equivalente de 0-360 en relacion con el total almacenado en $suma_valores
            $grados_ancho = ($v/$suma_valores) * 360;
            $grado_ini = $grado_fin;
            $grado_fin = $grado_ini + $grados_ancho;
            $color_hex = $arr_colores[$numcolor];
            imagefilledarc ($this->image, $cx, $cy, $w, $h, $grado_ini, $grado_fin , $this->arrPaleta[$color_hex], IMG_ARC_PIE);
            $numcolor++;
            $numvalor++;
        }
    }

    function _oscureceColor($colorHEX, $intensidad)
    {
        if(!$this->_esColorHEX($colorHEX)) {
            return false;
        }

        if($intensidad>1) {
            $intensidad=1;
        }

        // calculo la intensidad...
        $intensidadRGB = $intensidad*100*2.55;
        
        $colorRR = hexdec(substr($colorHEX, 0, 2)) - $intensidadRGB;
        $colorGG = hexdec(substr($colorHEX, 2, 2)) - $intensidadRGB;
        $colorBB = hexdec(substr($colorHEX, 4, 2)) - $intensidadRGB;

        if($colorRR<0) $colorRR=0; 
        if($colorGG<0) $colorGG=0; 
        if($colorBB<0) $colorBB=0;
    
        $strRR = dechex($colorRR);
        $strGG = dechex($colorGG);
        $strBB = dechex($colorBB);

        if(strlen($strRR)==1) $strRR = "0" . $strRR; 
        if(strlen($strGG)==1) $strGG = "0" . $strGG; 
        if(strlen($strBB)==1) $strBB = "0" . $strBB;
 
        return $strRR . $strGG . $strBB;
    }

    function _imageColorAllocateHEX($s){
        // tendria que comprobar aqui si el string es de la longitud correcta y contiene los caracteres correctos
        if($this->_esColorHex($s)) { 
            $bg_dec=hexdec($s);
            return imagecolorallocate($this->image,
                   ($bg_dec & 0xFF0000) >> 16,
                   ($bg_dec & 0x00FF00) >>  8,
                   ($bg_dec & 0x0000FF)
                   );
        } else {
            return false; // no se si esta bien esta parte
        }
    }

    function _esColorHex($s)
    {
        return ereg("^[[:digit:]ABCDEFabcdef]{6}$", $s);
    }

    function _estaColorEnPaleta($s)
    {
        return array_key_exists($s, $this->arrPaleta);
    }

    function _ingresarColorEnPaleta($colorHex)
    {
        if(!$this->_estaColorEnPaleta($colorHex)) {
            $this->arrPaleta[$colorHex]   = $this->_imageColorAllocateHEX($colorHex);
        }
        return true;
    }

    function _obtenerColor($colorHex) 
    {
        // Esta funcion maneja colores automaticamente

        $this->_ingresarColorEnPaleta($colorHex);
        return $this->arrPaleta[$colorHex];
    }

    function _alinearTextoUnix2($texto, $x, $y, $tipo="1") {
        // Parece que este tipo de texto es de ancho fijo de 5 pixeles
        // Primero calculo la longitud del texto
        $longitud = strlen($texto)*5;
        
        if($tipo==1) { // CENTRADO
            $xizq = $x - (int)$longitud/2;
            imagestring($this->image, 2, $xizq, $y, $texto, $this->_obtenerColor('000000'));
        } else if ($tipo==2) { // HACIA LA DER
            $xizq = $x - (int)$longitud;
            imagestring($this->image, 2, $xizq, $y, $texto, $this->_obtenerColor('000000'));
        } else { // HACIA LA IZQ
            imagestring($this->image, 2, $x, $y, $texto, $this->_obtenerColor('000000'));
        }
    }

    function escribirTextoUnix($x, $y, $texto, $colorHex='000000')
    {
        if(!$this->_esColorHex($colorHex)) $colorHex = '000000';
        imagestring($this->image, 2, $x, $y, $texto, $this->_obtenerColor($colorHex));

    }

    function dibujarLineaPunteada($x1, $y1, $x2, $y2, $colorHex="FF3333", $strPatron="x--")
    {
        if($x1>$x2) {
            $tmpx=$x1; $tmpy=$y1;
            $x1=$x2;   $y1=$y2;
            $x2=$tmpx; $y2=$tmpy;
        }    

        $longPatron = strlen($strPatron);

        if($strPatron{0}=="x" or $strPatron{0}=="X") {
            imagesetpixel($this->image, $x1, $y1, $this->_obtenerColor($colorHex));
        }

        $cuentaCaracter = 1;

        if(abs($x2-$x1)<abs($y2-$y1)) { // esta linea es mas vertical que horizontal

            if($y1>$y2) {
                $tmpx=$x1; $tmpy=$y1;
                $x1=$x2;   $y1=$y2;
                $x2=$tmpx; $y2=$tmpy;
            }


            for($i=($y1+1); $i<=$y2; $i++) {
                $x = $x1 + ($x2-$x1)/($i-$y1);
                if($strPatron{$cuentaCaracter}=="x" or $strPatron{$cuentaCaracter}=="X") {
                    imagesetpixel($this->image, $x, $i, $this->_obtenerColor($colorHex));
                }
                if($cuentaCaracter>=($longPatron-1)) {
                    $cuentaCaracter=0;
                } else {
                    $cuentaCaracter++;
                }
            }

        } else { // esta linea es mas horizontal que vertical

            if($x1>$x2) {
                $tmpx=$x1; $tmpy=$y1;
                $x1=$x2;   $y1=$y2;
                $x2=$tmpx; $y2=$tmpy;
            }

            for($i=($x1+1); $i<=$x2; $i++) {
                $y = $y1 + ($y2-$y1)/($i-$x1);
                if($strPatron{$cuentaCaracter}=="x" or $strPatron{$cuentaCaracter}=="X") {
                    imagesetpixel($this->image, $i, $y, $this->_obtenerColor($colorHex));
                }
                if($cuentaCaracter>=($longPatron-1)) {
                    $cuentaCaracter=0;
                } else {
                    $cuentaCaracter++;
                }
            }
        }
    }

    function _timestampAPixel($timestamp, $bordeIzquierdo)
    {
        // por ahora el tipo siempre es diario
        return ceil(((($this->anchoCuadricula*($timestamp-$this->timestampInicioGrafico))/($this->_duracionGraficoSegundos)) 
                + $bordeIzquierdo) + 1); // el anchoCuadricula es el ancho de la Grilla descontando ya el borde
                                      //

    }
}
?>
