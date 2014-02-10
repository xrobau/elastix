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
 * $Id: setupForm.class.php,v 1.0 17/01/2012 12:16:30 PM molvera Exp $
 */

include_once "libs/paloSantoForm.class.php";

class setupForm extends paloForm{

    private static
        $RUN = 'r',
        $NOT_FOUND = 'nf',
        $NOT_RUN = 'nr',
        $NOT_REGISTER = 'nre';

    public static
        $texts = array(
            'r'  => 'Running',
            'nf' => 'Not Installed',
            'nr' => 'Inactive',
            'nre' => 'Inactive',
        ),
        $class_html = array(
            'r'  => 'running',
            'nf' => 'not_installed',
            'nr' => 'not_running',
            'nre' => 'not_running',
        ),
        $buttons = array(
            'r'  => 'Stop service',
            'nf' => 'Start service',
            'nr' => 'Start service',
            'nre' => 'Start service',
        );

    private
        $status;

    public function __construct(&$smarty) {

        $this->status = self::$NOT_RUN;
        $arrFormElements = array(
            "status"   => array(
                "LABEL"                  => _tr("Status"),
                "REQUIRED"               => "no",
                "EDITABLE"               => "no",
                "INPUT_TYPE"             => "TEXT",
                "INPUT_EXTRA_PARAM"      => '',
                "VALIDATION_TYPE"        => "text",
                "VALIDATION_EXTRA_PARAM" => ""
            ),"server_key"   => array(
                "LABEL"                  => _tr("Server key"),
                "REQUIRED"               => "yes",
                "INPUT_TYPE"             => "TEXT",
                "INPUT_EXTRA_PARAM"      => array('style' => 'width: 350px;'),
                "VALIDATION_TYPE"        => "ereg",
                "VALIDATION_EXTRA_PARAM" => '^[0-9A-z\.#\$\w]+$',
            ),

        );
        parent::paloForm($smarty, $arrFormElements);
    }

    public function isRunning(){
        $this->status = self::$RUN;
    }
    
    public function noRegister(){
        $this->status = self::$NOT_REGISTER;
    }

    /**
     * Notifica a el formulario que el server key ya está registrado en elastix
     * Ésta función debe ser invocada sólo cuando se esté seguro de que la claveno lo ingresó
     */
    public function reallyHasServerKey(){
        $this->arrFormElements['server_key']['EDITABLE'] = $this->arrFormElements['server_key']['REQUIRED'] = 'no';
    }

    public function serviceNotEnable(){
        $this->status = self::$NOT_FOUND;
        $this->arrFormElements['server_key']['INPUT_EXTRA_PARAM'] = array('style' => 'width: 350px;','disabled'=> 'disabled');
    }

    private function translateNames(){
        //Botones
        $this->smarty->assign('SAVE',_tr('Apply changes'));
        $this->smarty->assign('EDIT',_tr('Save'));
        $this->smarty->assign('CANCEL',_tr('Cancel'));
        $this->smarty->assign('DELETE',_tr('Delete'));

        //Mensajes
        $this->smarty->assign('REQUIRED_FIELD',_tr('Required field'));
        $this->smarty->assign('CONFIRM_CONTINUE',_tr('Are you sure you wish to continue?'));
        //$this->smarty->assign("IMG", "");
        $this->smarty->assign('icon', 'images/list.png');
    }

    public function fetchForm($templateName, $title, $arrPreFilledValues = array()) {
        $this->translateNames();
        $arrPreFilledValues['status'] = _tr(self::$texts[$this->status]);
        $this->smarty->assign('class_status',self::$class_html[$this->status]);
        $this->smarty->assign('code_status',$this->status);
        $this->smarty->assign('SERVICE_VALUE',_tr(self::$buttons[$this->status]));
        return parent::fetchForm($templateName, $title, $arrPreFilledValues);
    }


    public function validateForm($arrCollectedVars) {
        //Pre default validations

        //Parent call (default validations)
        $bExito = parent::validateForm($arrCollectedVars);

        //Post default validations
        $arrErrores = array();


        //Check and report
        if(!empty($arrErrores)){
            if(!is_array($this->arrErroresValidacion)){
                $this->arrErroresValidacion = $arrErrores;
            }else{
                $this->arrErroresValidacion += $arrErrores;
            }
            return FALSE;
        }
        return $bExito;
    }
}
