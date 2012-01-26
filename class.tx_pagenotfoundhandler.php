<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Gernot Leitgab <leitgab@gmail.com>
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

require_once(PATH_tslib.'class.tslib_content.php');


/**
 * '404 page not found' error handler class for the 'pagenotfound_handler' extension.
 *
 * @author	Gernot Leitgab <leitgab@gmail.com>
 * @package	TYPO3
 * @subpackage	tx_pagenotfoundhandler
 */
class tx_pagenotfoundhandler {
	
	/**
	 * Main method
	 * called by the pageErrorHandler()-method in class 'tslib_fe'
	 *
	 * @param	array		$param: Some error parameters
	 * @param	array		$ref: 'tslib_fe' object
	 * @return	null
	 */
	function main($param, $ref) {
		// reset pageNotFound_handling, otherwise an endless loop in determineId() occurs for one level .html urls
		$GLOBALS['TSFE']->TYPO3_CONF_VARS['FE']['pageNotFound_handling'] = null;
		
		// setup to get plugin configurations
		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->getCompressedTCarray();
		$GLOBALS['TSFE']->initTemplate();
		$GLOBALS['TSFE']->getConfigArray();
		
		// set member variables
		$this->param = $param;
		$this->ref = $ref;
		$this->conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_pagenotfoundhandler.'];
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->base = str_replace(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST'), '', t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR'));
		$searchVars = $this->getSearchVars();
		$this->searchVars = $this->ignoreValues($searchVars);
		
		$redirect = false;
		
		// interpret indexed_search results
		if (intval($this->conf['redirect'])) {
			$results = $this->doIndexedSearch($this->searchVars);
			if ($this->conf['maxHighestRatingResults'] >= 0) {
				if (count($results) == 1 && $results[0]['rating'] >= $this->conf['minRating']) {
					$redirect = true;
				}
				else if ($results[0]['rating'] != $results[count($results) - 1]['rating'] && $results[0]['rating'] >= $this->conf['minRating']) {
					$redirect = true;
				}
			}
			else if ($results[0]['rating'] >= $this->conf['minRating']) {
				$redirect = true;
			}
		}
		
		// redirect to page or search
		if ($redirect) {
			$header = $this->conf['redirectHeader'];
			$location = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR') . $this->cObj->getTypoLink_URL($results[0]['page_id']);
		}
		else {
			$header = $this->conf['searchHeader'];
			$sword = urlencode(implode(' ' . $this->conf['operator'] . ' ', $this->searchVars));
			$location = t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR') . $this->cObj->getTypoLink_URL($this->conf['searchPid']) . '?tx_pagenotfoundhandler=1&tx_indexedsearch[sword]=' . $sword;
		}
		
		$this->redirect($header, $location);
	}
	
	
	/**
	 * Decides which method is used to get keywords to search with
	 *
	 * return	array
	 */
	function getSearchVars() {
		$searchVars = array();
		
		$referer = t3lib_div::getIndpEnv('HTTP_REFERER');
		if (intval($this->conf['referer']) && !empty($referer) && !substr_count($referer, t3lib_div::getIndpEnv('HTTP_HOST'))) {
			$searchVars = $this->getRefererUrlVars($referer);
		}
		
		if (!count($searchVars)) {
			$request = preg_replace('/^' . str_replace('/', '\/', $this->base) . '/', '', $this->param['currentUrl']);
			$requestParts = parse_url($request);
			$requestParts['path'] = preg_replace('/\.html$/i', '', $requestParts['path']);
			
			if ($this->conf['query']) {
				$searchVars = $this->getRequestUrlVars($requestParts['path'], $this->conf['pathSplitPattern']);
				$searchVars = array_merge($searchVars, $this->getRequestUrlVars($requestParts['query'], $this->conf['querySplitPattern']));
				$searchVars = array_unique($searchVars);
			}
			else {
				$searchVars = $this->getRequestUrlVars($requestParts['path'], $this->conf['pathSplitPattern']);
			}
		}
		
		return $searchVars;
	}
	
	
	/**
	 * Returns an array of keywords from a referer
	 * 
	 * @param	string		$referer: Referer-string
	 * @return	array
	 */
	function getRefererUrlVars($referer) {
		$searchVars = array();
		
		$refererParts = parse_url($referer);
		if (!empty($refererParts['query'])) {
			parse_str($refererParts['query'], $searchVarsParts);
			
			$searchKeys = array('query', 'search', 'q', 's', 'p');
			foreach ($searchKeys as $searchKey) {
				if (array_key_exists($searchKey, $searchVarsParts)) {
					$searchVars = $this->getRequestUrlVars($searchVarsParts[$searchKey], $this->conf['querySplitPattern']);
					break;
				}	
			}
		}
		else if (!empty($refererParts['path'])) {
			$searchVars = $this->getRequestUrlVars($refererParts['path'], $this->conf['pathSplitPattern']);
		}
		
		return $searchVars;
	}
	
	
	/**
	 * Returns an array of keywords from a string
	 *
	 * @param	string		$request: Request-string
	 * @return	array
	 */
	function getRequestUrlVars($request, $pattern) {
		$searchVars = array();
		
		$requestParts = preg_split('/' . str_replace('/', '\/', $pattern) . '/', $request, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($requestParts as $value) {
			$searchVars[] = strtolower($value);
		}
		
		return $searchVars;
	}
	
	
	/**
	 * Removes values from the ignore list
	 *
	 * @param	array		$searchVars: Keywords to search with
	 * @return	array
	 */
	function ignoreValues($searchVars) {
		$ignore = explode(',', preg_replace('/\s+/', '', $this->conf['ignore']));
		$searchVars = array_diff($searchVars, $ignore);
		
		return $searchVars;
	}
	
	
	/**
	 * Performs an indexed_search
	 *
	 * @param	array		$searchVars: Keywords to search with
	 * @return	array
	 */
	function doIndexedSearch($searchVars) {
		require_once(t3lib_extMgm::extPath('indexed_search').'pi/class.tx_indexedsearch.php');
		
		$result = array();
		
		$sWArr = array();
		foreach ($searchVars as $sword) {
			$sWArr[] = array('sword' => $sword, 'oper' => $this->conf['operator']);
		}
		
		$content = '';
		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_indexedsearch.'];
		if ($this->conf['maxHighestRatingResults'] >= 0) {
			$conf['_DEFAULT_PI_VARS.']['results'] = intval($this->conf['maxHighestRatingResults'] + 1);
		}
		else {
			$conf['_DEFAULT_PI_VARS.']['results'] = 1;
		}
		$indexedSearch = t3lib_div::makeInstance('tx_indexedsearch');
		$indexedSearch->cObj = $this->cObj;
		$indexedSearch->main($content, $conf);
		$rows = $indexedSearch->getResultRows($sWArr);
		
		if (is_array($rows)) {
			if (is_array($rows['firstRow'])) {
				$indexedSearch->firstRow = $rows['firstRow'];
				$indexedSearch->piVars['order'] = 'rank_flag';
			}
			foreach ($rows['resultRows'] as $key => $row) {
				$rows['resultRows'][$key]['rating'] = str_replace('%', '', $indexedSearch->makeRating($row));
			}
			$result = $rows['resultRows'];
		}
		
		return $result;
	}
	
	
	/**
	 * Redirects to a location with a status
	 *
	 * @param	string		$status: Status-header
	 * @param	string		$location: Location
	 * @return	null
	 */
	function redirect($status, $location) {
		// 'SERVER_PROTOCOL' cannot be fetched with t3lib_div::getIndpEnv()
		$protocol = $_SERVER['SERVER_PROTOCOL'];
		header($protocol . ' ' . $status);
		header('Location: ' . $location);
		
		exit;
	}
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pagenotfound_handler/class.tx_pagenotfoundhandler.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pagenotfound_handler/class.tx_pagenotfoundhandler.php']);
}

?>