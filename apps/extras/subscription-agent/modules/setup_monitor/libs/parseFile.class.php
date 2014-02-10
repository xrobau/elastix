<?php

/*
 * vim: set expandtab tabstop=4 softtabstop=4 shiftwidth=4:
 * Codificación: UTF-8
 * +----------------------------------------------------------------------+
 * | Copyright (c) 1997-2005 Palosanto Solutions S. A.                    |
 * +----------------------------------------------------------------------+
 * | Cdla. Nueva Kennedy Calle E 222 y 9na. Este                          |
 * | Telfs. 2283-268, 2294-440, 2284-356                                  |
 * | Guayaquil - Ecuador                                                  |
 * +----------------------------------------------------------------------+
 * | Este archivo fuente está sujeto a las políticas de licenciamiento    |
 * | de Palosanto Solutions S. A. y no está disponible públicamente.      |
 * | El acceso a este documento está restringido según lo estipulado      |
 * | en los acuerdos de confidencialidad los cuales son parte de las      |
 * | políticas internas de Palosanto Solutions S. A.                      |
 * | Si Ud. está viendo este archivo y no tiene autorización explícita    |
 * | de hacerlo, comuníquese con nosotros, podría estar infringiendo      |
 * | la ley sin saberlo.                                                  |
 * +----------------------------------------------------------------------+
 * | Autores: Manuel Olvera <molvera@palosanto.com>                       |
 * +----------------------------------------------------------------------+
 * $Id: parseFile.class.php,v 1.0 27/09/2011 02:36:31 PM molvera Exp $
 */

/**
 * Clase obtiene el contenido de un archivo, lo parsea según las configuraciones
 * impuestas y encapsula toda la información y prepara la instancia como si fuera
 * un arreglo.
 *
 * @author Manuel Olvera
 */
class parseFile implements ArrayAccess, Countable,  Iterator{
    protected
        $data_file = array(),
        $iOptions,
        $comment_charters,
        $last_modified,
        $extras,
        $headers = array();

    /**
     *
     */
    const IS_DEFAULT = 0;

    /**
     *
     */
    const NO_PARSE = 1;

    /**
     *
     */
    const NO_DEFAULT = 2;

    /**
     *
     */
    const ADD_COMMENTS = 4;

    /**
     *
     */
    const SECTIONS = 8;

    /**
     *
     */
    const COLLECT_REPEATED = 16;

    /**
     * @param string $path_file ruta del archivo a parsear.
     * @param int $options valor de opciones binarias para realizar determinadas acciones sobre el parseo del archivo.
     * @param array $extra contiene información adicional para realizar el parseo
     *
     * @throws InvalidArgumentException Si el archivo no existe.
     */
    public function __construct($path_file, $options = null, $extra=array()) {
        if(!file_exists($path_file)) throw new InvalidArgumentException(_tr("File not found '%s'"));
        if(!is_readable($path_file)) throw new InvalidArgumentException(_tr("File could not be readable '%s'"));

        if(is_null($options))   $options = self::NO_PARSE | self::ADD_COMMENTS;

        //Obtengo el timestamp (int) de la fecha de última modificación del archivo con motivos netamente de auditoria.
        $this->last_modified = filemtime($path_file);

        //reconociendo las opciones de parseo
        $this->iOptions = $options;

        //
        $this->comment_charters = array_key_exists('comments',$extra)?str_replace('|', '\|', $extra['comments']):'#;';

        //Información adicional de ayuda al parseo
        $this->extras = $extra;

        //Headers
        if($this->iOptions & self::SECTIONS){
            if(!empty($extra['headers']))       $this->headers = $extra['headers'];
            if(!empty($this->headers['start'])) $this->headers['start'].= '|';
            else                                $this->headers['start'] = '';
            $this->headers['start'] = '#'.preg_replace('@#@','\#',$this->headers['start']).'^\[(.+)\]$#';

            if(!empty($this->headers['end'])) $this->headers['end'] = '#'.preg_replace('@#@','\#',$this->headers['end']).'#';
        }

        if(($options & self::NO_PARSE) || ($options & self::NO_DEFAULT))    $this->data_file = $this->parse_file($path_file);
        elseif(version_compare(PHP_VERSION, '5.3.0', '<'))                  $this->data_file = parse_ini_file($path_file, $this->iOptions & self::SECTIONS);
        else                                                                $this->data_file = parse_ini_file($path_file, $this->iOptions & self::SECTIONS, INI_SCANNER_RAW);
    }

    /**
     * Funcion invocada por las funciones isset y empty para conocer si existe
     * un elemento dentro del arreglo de elementos (lineas)
     *
     * @param mixed $offset
     * @return type
     */
    public function offsetExists($offset) {return array_key_exists($offset,$this->data_file);}
    public function offsetGet($offset) {return $this->data_file[$offset];}
    public function offsetSet($offset, $value) {$this->data_file[$offset] = $value;}
    public function offsetUnset($offset) {unset($this->data_file[$offset]);}

    public function count() {return count($this->data_file);}
    public function rewind() {reset($this->data_file);}
    public function current() {return current($this->data_file);}
    public function key() {return key($this->data_file);}
    public function next() {return next($this->data_file);}

    public function valid() {
        $key = key($this->data_file);
        return ($key !== NULL && $key !== FALSE);
    }

    final protected function getHeaderSection($data){
        if($this->iOptions & self::SECTIONS && preg_match($this->headers['start'], $data, $match)){
            return $match[1];
        }
        return '';
    }

    final protected function isEndHeaderSection($data){
        if(empty($this->headers['end']) || !($this->iOptions & self::SECTIONS)) return false;
        return preg_match($this->headers['end'], $data);
    }

    final protected function parse_file($file){
        $arrFileData = file($file);
        $arrClearData = array();
        $stack_pointer = array(&$arrClearData);

        foreach($arrFileData as $line){
            $line = trim($line);
            if(!empty($line) && ($this->iOptions & self::NO_PARSE || ($this->iOptions & self::ADD_COMMENTS && preg_match("|^[{$this->comment_charters}]|", $line)))){
                $stack_pointer[0][] = $line;
                continue;
            }

            if(empty($line) || (!($this->iOptions & self::ADD_COMMENTS) && preg_match("|^[{$this->comment_charters}]|", $line))) continue;

            $header = $this->getHeaderSection($line);
            if('' !== $header){
                if(count($stack_pointer)>1 && (!array_key_exists('end', $this->headers) || empty($this->headers['end']))) //Si no hay fin de bloque definido, regreso al elemento padre de forma automática
                    array_shift($stack_pointer);

                $stack_pointer[0][$header] = array();
                //array_unshift no es recomendado porque devuelve el nuevo arreglo con elementos pasados por valor y no mantiene sus referencia
                $new_stack = array(&$stack_pointer[0][$header]);
                foreach($stack_pointer as $k => $data)    $new_stack[] =& $stack_pointer[$k];
                $stack_pointer = $new_stack;
                continue;
            }
            if($this->isEndHeaderSection($line)){
                array_shift($stack_pointer);
                continue;
            }
            if(preg_match('#^\s*([^\s=]+)(\s*=?\s*)(.*)$#i', $line, $match)){
                if(empty($match[3]) && empty($match[2]))    $stack_pointer[0][] = $match[1];
                else                                        $this->push_data(trim($match[1]), trim($match[3]), $stack_pointer[0]);
            }
        }
        return $arrClearData;
    }

    final protected function push_data($key,$value,&$arrDest){
        if(!($this->iOptions & self::COLLECT_REPEATED?TRUE:!array_key_exists($key, $arrDest))) return;

        if(array_key_exists($key, $arrDest)){
            if(!is_array($arrDest[$key])){
                $tmp = array($arrDest[$key]);
                unset($arrDest[$key]);
                $arrDest[$key] = $tmp;
            }
            $arrDest[$key][] = $value;
        }else
            $arrDest[$key] = $value;
    }

    final public function getLastDateUpdated($format = 'Y-m-d H:i:s'){
        return date($format,$this->last_modified);
    }
}
