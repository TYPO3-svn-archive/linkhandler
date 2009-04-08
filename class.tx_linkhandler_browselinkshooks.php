<?php
/***************************************************************
 *  Copyright notice
 *
 *  Copyright (c) 2008, Daniel P�tzinger <daniel.poetzinger@aoemedia.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

if (!defined ('TYPO3_MODE'))
	die ('Access denied.');

/**
 * hook to adjust linkwizard (linkbrowser)
 *
 * @author	Daniel Poetzinger (AOE media GmbH)
 * @package TYPO3
 * @subpackage linkhandler
 */


// include defined interface for hook
// (for TYPO3 4.x usage this interface is part of the patch)
$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['linkhandler']);
if ($confArr['applyPatch']==1) {
	require_once (t3lib_extMgm::extPath('linkhandler').'patch/interfaces/interface.t3lib_browselinkshook.php');
}
else {
	require_once (PATH_t3lib.'interfaces/interface.t3lib_browselinkshook.php');
}



require_once (t3lib_extMgm::extPath('linkhandler').'classes/class.tx_linkhandler_recordTab.php');





class tx_linkhandler_browselinkshooks implements t3lib_browseLinksHook {

	/**
	* the browse_links object
	*/
	protected $pObj;

	protected $allAvailableTabHandlers=array();

	/**
	 * initializes the hook object
	 *
	 * @param	browse_links	parent browse_links object
	 * @return	void
	 */
	function init($pObj,$params) {
		$this->pObj=&$pObj;
		$this->_checkConfigAndGetDefault();
		$tabs=$this->getTabsConfig();
		foreach ($tabs as $key=>$tabConfig) {
			if ($this->isRTE()) {
				$this->pObj->anchorTypes[] = $key; //for 4.3
			}
		}
		$this->allAvailableTabHandlers=$this->getAllRegisteredTabHandlerClassnames();

	}

	/* checks if
	*	$this->pObj->thisConfig['tx_linkhandler.'] is set, and if not it trys to load default from
	*	TSConfig key mod.tx_linkhandler.
	*	(in case the hook is called from a RTE, this configuration might exist because it is configured in RTE.defaul.tx_linkhandler)
	*		In mode RTE: the parameter RTEtsConfigParams have to exist
	*		In mode WIzard: the parameter P[pid] have to exist
	*/
	function _checkConfigAndGetDefault() {
		global $BE_USER;
		if ($this->pObj->mode=='rte') {
			$RTEtsConfigParts = explode(':',$this->pObj->RTEtsConfigParams);
			$RTEsetup = $BE_USER->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
			$this->pObj->thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		}

		elseif (!is_array($this->pObj->thisConfig['tx_linkhandler.'])) {
			$P=t3lib_div::_GP('P');
			$pid=$P['pid'];
			$modTSconfig = $GLOBALS["BE_USER"]->getTSConfig("mod.tx_linkhandler",t3lib_BEfunc::getPagesTSconfig($pid));
			//print_r($modTSconfig);
			$this->pObj->thisConfig['tx_linkhandler.']=$modTSconfig['properties'];
		}

	}

	/**
	 * adds new items to the currently allowed ones and returns them
	 *
	 * @param	array	currently allowed items
	 * @return	array	currently allowed items plus added items
	 */
	function addAllowedItems($allowedItems) {
		if (is_array($this->pObj->thisConfig['tx_linkhandler.'])) {
			foreach ($this->pObj->thisConfig['tx_linkhandler.'] as $name => $tabConfig) {
				if (is_array($tabConfig)) {
					$key=substr($name,0,-1);
					$allowedItems[]=$key;
				}
			}
		}
		return $allowedItems;
	}

	/**
	 * checks the current URL and returns a info array. This is used to
	 *	tell the link browser which is the current tab based on the current URL.
	 *	function should at least return the $info array.
	 *
	 * @param	string		$href
	 * @param	string		$siteUrl
	 * @param	array		$info		Current info array.
	 * @return	array 				$info		a infoarray for browser to tell them what is current active tab
	 */
	function parseCurrentUrl($href,$siteUrl,$info) {

			//depending on link and setup the href string can contain complete absolute link
			if (substr($href,0,7)=='http://') {
				if ($_href=strstr($href,'?id=')) {
					$href=substr($_href,4);
				}
				else {
					$href=substr (strrchr ($href, "/"),1);
				}
			}
			//ask the registered tabHandlers:
			foreach ($this->allAvailableTabHandlers as $handler) {
				$result=call_user_func($handler.'::getLinkBrowserInfoArray',$href,$this->getTabsConfig());
				if (count($result)>0 && is_array($result)) {

					return array_merge($info,$result);
				}
			}
			return $info;
	}

	/**
	* returns a array of names available tx_linkhandler_tabHandler
	*/
	protected function getAllRegisteredTabHandlerClassnames() {
		$default=array('tx_linkhandler_recordTab');

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['linkhandler/class.tx_linkhandler_browselinkshooks.php'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['linkhandler/class.tx_linkhandler_browselinkshooks.php'] as $tabHandler) {
				list($file,$class) = t3lib_div::revExplode(':',$tabHandler,2);
				include_once($file);
				$default[]=$class;
			}
		}
		return $default;
	}

	/**
	* returns the complete configuration (tsconfig) of all tabs
	**/
	private  function getTabsConfig() {
		$tabs=array();
		if (is_array($this->pObj->thisConfig['tx_linkhandler.'])) {
			foreach ($this->pObj->thisConfig['tx_linkhandler.'] as $name => $tabConfig) {
				if (is_array($tabConfig)) {
					$key=substr($name,0,-1);
					$tabs[$key]=$tabConfig;
				}
			}
		}
		return $tabs;
	}
	/**
	* returns config for a single tab
	*/
	private function getTabConfig($tabKey) {
		$conf=$this->getTabsConfig();
		return $conf[$tabKey];
	}

	/**
	 * modifies the menu definition and returns it
	 *
	 * @param	array	menu definition
	 * @return	array	modified menu definition
	 */
	function modifyMenuDefinition($menuDef) {
		$tabs=$this->getTabsConfig();
		foreach ($tabs as $key=>$tabConfig) {
			$menuDef[$key]['isActive'] = $this->pObj->act==$key;
			$menuDef[$key]['label'] = $tabConfig['label']; // $LANG->getLL('records',1);
			$menuDef[$key]['url'] = '#';
			$addPassOnParams.=$this->getaddPassOnParams();
			$menuDef[$key]['addParams'] = 'onclick="jumpToUrl(\'?act='.$key.'&editorNo='.$this->pObj->editorNo.'&contentTypo3Language='.$this->pObj->contentTypo3Language.'&contentTypo3Charset='.$this->pObj->contentTypo3Charset.$addPassOnParams.'\');return false;"';
		}

		return $menuDef;
	}

	/**
	 * returns additional addonparamaters - required to keep several informations for the RTE linkwizard
	 */
	protected function getaddPassOnParams() {
		$urlParams = '';
		if (!$this->isRTE()) {
			$P2=t3lib_div::_GP('P');
			if (is_array($P2) && !empty($P2) ) {

				$urlParams = t3lib_div::implodeArrayForUrl('P',$P2);
			}
		}
		return $urlParams;
	}

	/**
	* returns if the current linkwizard is RTE or not
	**/
	protected function isRTE() {
		if ($this->pObj->mode=='rte') {
			return true;
		}
		else {
			return false;
		}

	}

	/**
	 * returns a new tab for the browse links wizard
	 *
	 * @param	string		current link selector action
	 * @return	string		a tab for the selected link action
	 */
	function getTab($act) {

		global $LANG;
		if (!$this->_isOneOfLinkhandlerTabs($act))
		    return false;

		if ($this->isRTE()) {
			if (isset($this->pObj->classesAnchorJSOptions)) {
				$this->pObj->classesAnchorJSOptions[$act]=@$this->pObj->classesAnchorJSOptions['page']; //works for 4.1.x patch, in 4.2 they make this property protected! -> to enable classselector in 4.2 easoiest is to path rte.
			}
		}

		$configuration=$this->getTabConfig($act);
		//get current href value (diffrent for RTE and normal browselinks)
		if ($this->isRTE()) {
           $currentValue=$this->pObj->curUrlInfo['value'];
       	}
       	else {
           $currentValue=$this->pObj->P['currentValue'];
       	}
       	//get the tabHandler
		$tabHandlerClass='tx_linkhandler_recordTab'; //the default tabHandler
		if (class_exists($configuration['tabHandler'])) {
			$tabHandlerClass=$configuration['tabHandler'];
		}
		$tabHandler=new $tabHandlerClass($this->pObj,$this->getaddPassOnParams,$configuration,$currentValue,$this->isRTE());
		$content=$tabHandler->getTabContent();

		return $content;
	}


    function _isOneOfLinkhandlerTabs ($key)
    {
        foreach ($this->pObj->thisConfig['tx_linkhandler.'] as $name => $tabConfig) {
            if (is_array($tabConfig)) {
                $akey = substr($name, 0, - 1);
                if ($akey == $key)
                    return true;
            }
        }
        return false;
    }
}


?>