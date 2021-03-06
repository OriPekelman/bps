<?php

/* Include Files *********************/
require_once("../../libs/env.php");
require_once("../admin/authUtils.php");
require_once "../../libs/RESTClient.php";
/*************************************/

// If the user isn't logged in, send to the login page.
// FIXME - allow not logged in state - will be all no-edit
if(($login_state != BPS_LOGGED_IN) && ($login_state != BPS_REG_PENDING)){
	header( 'Location: ' . $CFG->wwwroot .
					'/login?redir=' .$_SERVER['REQUEST_URI'] );
	die();
}

$t->assign('page_title', 'Corpus Details'.$CFG->page_title_default);
$maxKTEI = 15000;
$maxKAssertions = 1000;
$maxfilesizeTEI = $maxKTEI * 1024;
$t->assign('maxfilesizeTEI', $maxfilesizeTEI);
$maxfilesizeAssertions = $maxKAssertions * 1024;
$t->assign('maxfilesizeAssertions', $maxfilesizeAssertions);

$canUpdateCorpus = false;
if(currUserHasPerm( 'CorpusUpdate' )) {
	$canUpdateCorpus = true;
}

$style_block = "<style>
td.title { border-bottom: 2px solid black; font-weight:bold; text-align:left; 
		font-style:italic; color:#777777; }
td.corpus_label { font-weight:bold; color:#61615f; }
td.corpusname { font-weight:bold; }
td.corpusdesc p { font-weight:bold; }
td.corpusX { border-bottom: 1px solid black; }
td.corpusdesc textarea { font-family: Arial, Helvetica, sans-serif; padding:2px;}
td.corpusndocs { text-align:center; padding-right:10px;}
form.form_row  { padding:0px; margin:2px;}
div.form_row  { padding:5px 0px 5px 0px; border-bottom: 1px solid black; }
div.docs_row  { padding:5px 0px 5px 0px; border-bottom: 1px solid black; }
td.document { font-weight:bold; }
p.nav-right { float:right; }
</style>";

$t->assign("style_block", $style_block);

// $themebase = $CFG->wwwroot.'/themes/'.$CFG->theme;
unset($errmsg);

$opmsg = false;

function getCorpus($CFG,$id){
	global $opmsg;

	$rest = new RESTclient();
	$url = $CFG->serverwwwroot.$CFG->svcsbase."/corpora/".$id;
	$rest->createRequest($url,"GET");
	// Get the results in JSON for easier manipulation
	$rest->setJSONMode();
	if($rest->sendRequest()) {
		$ServCorpOutput = $rest->getResponse();
		$result = json_decode($ServCorpOutput, true);
		$corpObj = &$result['corpus'];
		$corpus = array(
			'id' => $corpObj['id'],
			'name' => $corpObj['name'], 
			'ownerId' => $corpObj['ownerId'], 
			'nDocs' => $corpObj['ndocs'],
			'medianDocDate' => $corpObj['medianDocDate'],
			'description' => $corpObj['description']);
		unset($corpObj);
		return $corpus;
	} else if($rest->getStatus() == 404) {
		$opmsg = "Bad or illegal corpus specifier. ";
	} else {
		$opmsg = $rest->getError();
	}
	return false;
}

function getCorpusDocs($CFG,$cid,$nid,$role,$order,$medianDocDate) {
	global $opmsg;

	$rest = new RESTclient();
	$url = $CFG->serverwwwroot.$CFG->svcsbase."/corpora/".$cid."/documents";
	$qch = "?";
	if(!empty($nid)) {
		$url .= "?name=".$nid;
		$qch = "&";
	}
	if(!empty($role)) {
		$url .= $qch."role=".$role;
		$qch = "&";
	}
	if(!empty($order)) {
		$url .= $qch."o=".$order;
	}
	$rest->createRequest($url,"GET");
	// Get the results in JSON for easier manipulation
	$rest->setJSONMode();
	if($rest->sendRequest()) {
		$ServCorpDocsOutput = $rest->getResponse();
		$results = json_decode($ServCorpDocsOutput, true);
		$documents = array();
		foreach($results as &$result) {
			$docObj = &$result['document'];
			$docDate = (isset($docObj['dateString'])&&$docObj['dateString']!=0)?
						$docObj['dateString']:$medianDocDate;
			$document = array(	'id' => $docObj['id'],
				'alt_id' => isset($docObj['alt_id'])?$docObj['alt_id']:null,
				'primaryPubl' => isset($docObj['primaryPubl'])?$docObj['primaryPubl']:null, 
				'notes' => isset($docObj['notes'])?$docObj['notes']:null,
				'sourceURL' => isset($docObj['sourceURL'])?$docObj['sourceURL']:null,
				'xml_id' => isset($docObj['xml_id'])?$docObj['xml_id']:null,
				'date_str' => $docDate
			);
			array_push($documents, $document);
			// Supposed to help with efficiency (dangling refs?)
			unset($result);
			unset($docObj);
		}
		return $documents;
	}
	$opmsg = $rest->getError();
	return false;
}

function getNameInCorpus($CFG,$cid, $nid) {
	global $opmsg;

	$rest = new RESTclient();
	$url = $CFG->serverwwwroot.$CFG->svcsbase."/corpora/".$cid."/names/".$nid;
	$rest->createRequest($url,"GET");
	// Get the results in JSON for easier manipulation
	$rest->setJSONMode();
	if($rest->sendRequest()) {
		$ServNameOutput = $rest->getResponse();
		$result = json_decode($ServNameOutput, true);
		$nameObj = &$result['name'];
		$nameStr = $nameObj['name'];
		unset($nameObj);
		return $nameStr;
	} else if($rest->getStatus() == 404) {
		$opmsg = "Bad or illegal corpus+name specifier. ";
	} else {
		$opmsg = $rest->getError();
	}
	return false;
}

function getCorpusNames($CFG,$id,$roleFilter,$genderFilter,$typeFilter,$orderBy) {
	global $opmsg;

	$rest = new RESTclient();
	$url = $CFG->serverwwwroot.$CFG->svcsbase."/corpora/".$id."/names";
	$first = true;
	if(!empty($typeFilter)) {
		$url .= "?type=".$typeFilter;
		$first = false;
	}
	if(!empty($roleFilter)) {
		$url .= ($first?'?':'&')."role=".$roleFilter;
		$first = false;
	}
	if(!empty($genderFilter)) {
		$url .= ($first?'?':'&')."gender=".$genderFilter;
	}
	if(!empty($orderBy)) {
		$url .= ($first?'?':'&')."o=".$orderBy;
	}
	$rest->createRequest($url,"GET");
	// Get the results in JSON for easier manipulation
	$rest->setJSONMode();
	if($rest->sendRequest()) {
		$ServNamesOutput = $rest->getResponse();
		$results = json_decode($ServNamesOutput, true);
		$names = array();
		foreach($results as &$result) {
			$nameObj = &$result['name'];
			array_push($names, array(	'id' => $nameObj['id'], 
				'name' => $nameObj['name'], 'gender' => $nameObj['gender'],
				'nametype' => $nameObj['nametype'], 'docCount' => $nameObj['usedInDocCount'],
				'totalCount' => $nameObj['usedTotalCount']
			));
			// Supposed to help with efficiency (dangling refs?)
			unset($result);
			unset($nameObj);
		}
		return $names;
	}
	$opmsg = $rest->getError();
	return null;
}


function getCorpusRoles($CFG,$cid){
	global $opmsg;

	$rest = new RESTclient();
	$url = $CFG->serverwwwroot.$CFG->svcsbase."/corpora/".$cid."/activityRoles";
	$rest->createRequest($url,"GET");
	// Get the results in JSON for easier manipulation
	$rest->setJSONMode();
	if($rest->sendRequest()) {
		$ServRolesOutput = $rest->getResponse();
		$results = json_decode($ServRolesOutput, true);
		$roles = array();
		foreach($results as &$result) {
			$roleObj = &$result['actrole'];
			array_push($roles, $roleObj['name']); 
			// Supposed to help with efficiency (dangling refs?)
			unset($result);
			unset($roleObj);
		}
		return $roles;
	}
	$opmsg = $rest->getError();
	return false;
}

if(!isset($_GET['view'])) {
	$view = 'docs';
} else {
	$view = $_GET['view'];
	if($view!='docs'&&$view!='pnames'&&$view!='cnames'&&$view!='admin')
		$view = 'docs';
	else if($view=='admin' && !$canUpdateCorpus)
		$view = 'docs';
}

if(!isset($_GET['id'])) {
	$errmsg = "Missing corpus specifier. ";
} else {
	$corpusID = $_GET['id'];
	$corpus = getCorpus($CFG,$corpusID);
	if(!$corpus){
		if($opmsg){
			$errmsg = "Problem getting Corpus details: ".$opmsg;
		} else {
			$errmsg = "Bad or illegal corpus specifier. ";
		}
	} else {
		$t->assign('corpusID', $corpusID);
		$t->assign('corpus', $corpus);

		// Verify that the current user is the owner, if the view is admin; if not, switch to docs view
		$owner_id = getCurrUser();
		if( $owner_id != $corpus['ownerId'] ) {
			$canUpdateCorpus = false;
			if($view=='admin') {
				$view = 'docs';
			}
		}
		if($view=='admin') {
			$corp_file = $CFG->corpusdir.'/'.$corpusID.'/tei/corpus.xml';
			if(file_exists($corp_file)) {
				$t->assign('corpus_file', $corp_file);
				$teiloc = $CFG->wwwroot.$CFG->svcsbase.'/corpora/'.$corpusID.'/tei';
				$t->assign('teiloc', $teiloc);
				$teisummaryloc = $teiloc.'/summary';
				$t->assign('teisummaryloc', $teisummaryloc);
			}
			$dates_file = $CFG->corpusdir.'/'.$corpusID.'/assertions/dates.xml';
			if(file_exists($dates_file)) {
				$t->assign('dates_file', $dates_file);
			}
		} else if($view=='docs') {
			$nameIDFilter = isset($_GET['name'])?($_GET['name']):null;
			$roleFilter = isset($_GET['role'])?($_GET['role']):null;
			$orderBy = isset($_GET['o'])?($_GET['o']):null;
			$docs = getCorpusDocs($CFG,$corpusID, $nameIDFilter, $roleFilter, $orderBy,
															'<em>('.$corpus['medianDocDate'].'?)</em>');
			if($docs) {
				$t->assign('documents', $docs);
				if(!empty($nameIDFilter)) {
					$t->assign('nameFilter', $nameIDFilter);
					$nameFilterName = getNameInCorpus($CFG,$corpusID, $nameIDFilter);
					$t->assign('nameFilterName', empty($nameFilterName)? "(Unavailable)":$nameFilterName);
					if(!empty($roleFilter)) {
						$t->assign('roleFilterName', $roleFilter);
					}
				}
			} else if($opmsg){
				$errmsg = "Problem getting Corpus documents: ".$opmsg;
			}
		} else if($view=='pnames') {
			$roleFilter = isset($_GET['role'])?($_GET['role']):null;
			$genderFilter = isset($_GET['gender'])?($_GET['gender']):null;
			$orderBy = isset($_GET['o'])?($_GET['o']):null;
			$names = getCorpusNames($CFG,$corpusID, $roleFilter, $genderFilter,
																'person',$orderBy);
			if(isset($names)) {
				$t->assign('names', $names);
				$t->assign('type', 'Person');
				$t->assign('view', $view);
				$t->assign('orderBy', $orderBy);
				if(!empty($roleFilter))
					$t->assign('roleFilter', $roleFilter);
				if(!empty($genderFilter))
					$t->assign('genderFilter', $genderFilter);
				$roles = getCorpusRoles($CFG,$corpusID);
				if($roles) {
					$t->assign('roles', $roles);
				} else if($opmsg){
					$errmsg = "Problem getting Corpus roles: ".$opmsg;
				}
			} else if($opmsg){
				$errmsg = "Problem getting Corpus names: ".$opmsg;
			}
		} else if($view=='cnames') {
			$roleFilter = isset($_GET['role'])?($_GET['role']):null;
			$genderFilter = isset($_GET['gender'])?($_GET['gender']):null;
			$orderBy = isset($_GET['o'])?($_GET['o']):null;
			$t->assign('view', $view);
			$names = getCorpusNames($CFG,$corpusID, $roleFilter, null, 'clan',$orderBy);
			if(isset($names)) {
				$t->assign('names', $names);
				$t->assign('type', 'Clan');
				$t->assign('orderBy', $orderBy);
				if(!empty($roleFilter))
					$t->assign('roleFilter', $roleFilter);
				$roles = getCorpusRoles($CFG,$corpusID);
				if($roles) {
					$t->assign('roles', $roles);
				} else if($opmsg){
					$errmsg = "Problem getting Corpus roles: ".$opmsg;
				}
			} else if($opmsg){
				$errmsg = "Problem getting Corpus names: ".$opmsg;
			}
		}
	}
}

$t->assign('canUpdateCorpus', ($canUpdateCorpus?1:0));
$t->assign("currSubNav", $view);

$filter_script_block = '
<script>
function filterNames(corpId,orderBy) {
	var url = "/corpora/corpus?id="+corpId+"&view='.$view.'&o="+orderBy;
	var roleFilterSelEl = document.getElementById("RoleFilterSel");
	var index = roleFilterSelEl.selectedIndex;
	var roleFilter = roleFilterSelEl.options[index].value;
	if(roleFilter!="All")
		url += "&role="+roleFilter;
	var genderFilterSelEl = document.getElementById("GenderFilterSel");
	if(genderFilterSelEl != null ) {
		index = genderFilterSelEl.selectedIndex;
		var genderFilter = genderFilterSelEl.options[index].value;
		if(genderFilter!="All")
			url += "&gender="+genderFilter;
	}
	//alert( "Navigating to: " + url );
	window.location.href = url;
}

function filterDocsByName(corpId,nameId) {
	var url = "/corpora/corpus?id="+corpId+"&view=docs&name="+nameId;
	var roleFilterSelEl = document.getElementById("RoleFilterSel");
	var index = roleFilterSelEl.selectedIndex;
	var roleFilter = roleFilterSelEl.options[index].value;
	if(roleFilter!="All")
		url += "&role="+roleFilter;
	//alert( "Navigating to: " + url );
	window.location.href = url;
}

</script>';
$admin_script_block = '
<script type="text/javascript" src="/scripts/setupXMLHttpObj.js"></script>
<script type="text/javascript" src="/scripts/corpus.js"></script>
<script>

// The ready state change callback method for update.
function updateCorpusRSC() {
  if (xmlhttp.readyState==4) {
		if( xmlhttp.status == 200 ) {
			// Maybe this should change the cursor or something
			setStatusP("Corpus updated.");
	    //alert( "Response: " + xmlhttp.status + " Body: " + xmlhttp.responseText );
		} else {
			alert( "Error encountered when trying to update corpus.\nResponse: "
			 				+ xmlhttp.status + "\nBody: " + xmlhttp.responseText );
		}
	}
}


function updateCorpus(corpusID, name) {
	// Could change cursor and disable button until get response
	var descTextEl = document.getElementById("D_"+corpusID);
	var desc = descTextEl.value;
	if( desc.length <= 2 ) {
		alert( "You must enter a description that is at least 3 characters long" );
		return;
	}
	if( !xmlhttp ) {
		alert( "Cannot update corpus - no http obj!\n Please advise BPS support." );
		return;
	}
	var url = "'.$CFG->svcsbase.'/corpora/"+corpusID;
	var args = prepareCorpusXML(corpusID, name, desc,"");
	//alert( "Preparing request: PUT: "+url+"?"+args );
	xmlhttp.open("PUT", url, true);
	xmlhttp.setRequestHeader("Content-Type",
														"application/xml" );
	xmlhttp.onreadystatechange=updateCorpusRSC;
	xmlhttp.send(args);
	//window.status = "request sent: POST: "+url+"?"+args;
	enableElement( "U_"+corpusID, false );
}

// The ready state change callback method for the processTEI method
function processTEIRSC() {
  if (xmlhttp.readyState==4) {
		if( xmlhttp.status == 200 ) {
			// Maybe this should change the cursor or something
			setStatusP("Corpus rebuilt.");
			//alert( "Response: " + xmlhttp.status + " Body: " + xmlhttp.responseText );
			//window.location.reload();
		} else {
			alert( "Error encountered when trying to rebuild corpus.\nResponse: "
			 				+ xmlhttp.status + "\nBody: " + xmlhttp.responseText );
		}
	enableElement( "processTEIBtn", true );
	}
}


function processTEI(corpusID) {
	if( !xmlhttp ) {
		alert( "Cannot update corpus - no http obj!\n Please advise BPS support." );
		return;
	}
	var url = "'.$CFG->svcsbase.'/corpora/"+corpusID+"/tei";
	//alert( "Preparing request: PUT: "+url);
	xmlhttp.open("PUT", url, true);
	xmlhttp.onreadystatechange=processTEIRSC;
	xmlhttp.send(null);
	enableElement( "processTEIBtn", false );
}

// The ready state change callback method for the processDates method
function processDatesRSC() {
  if (xmlhttp.readyState==4) {
		if( xmlhttp.status == 200 ) {
			// Maybe this should change the cursor or something
			setStatusP("Date Assertions processed.");
	    //alert( "Response: " + xmlhttp.status + " Body: " + xmlhttp.responseText );
			//window.location.reload();
		} else {
			alert( "Error encountered when trying to process date assertions.\nResponse: "
			 				+ xmlhttp.status + "\nBody: " + xmlhttp.responseText );
		}
	enableElement( "processDatesBtn", true );
	}
}


function processDates(corpusID) {
	if( !xmlhttp ) {
		alert( "Cannot update corpus - no http obj!\n Please advise BPS support." );
		return;
	}
	var url = "'.$CFG->svcsbase.'/corpora/"+corpusID+"/dates";
	//alert( "Preparing request: PUT: "+url);
	xmlhttp.open("PUT", url, true);
	xmlhttp.onreadystatechange=processDatesRSC;
	xmlhttp.send(null);
	enableElement( "processDatesBtn", false );
}

//
// This should go into a utils.js - how to include?
function enableElement( elID, sense ) {
	var el = document.getElementById(elID);
	el.disabled = !sense;
}


function setStatusP(str) {
	var el = document.getElementById("statusP");
	el.innerHTML = str;
}

function limitChars( field, maxlimit ) {
  if ( field.value.length > maxlimit )
  {
    field.value = field.value.substring( 0, maxlimit-1 );
    alert( "Description can only be 255 characters in length." );
    return false;
  }
	return true;
}

</script>';

if(isset($errmsg))
	$t->assign('errmsg', $errmsg);

if($view=='docs') {
	$script_block = '';
} else if($view=='admin') {
	$script_block = $admin_script_block;
} else {
	$script_block = $filter_script_block;
}

$t->assign("script_block", $script_block);


if($view=='docs') {
	$t->display('corpus_docs.tpl');
} else if($view=='pnames') {
	$t->display('corpus_persnames.tpl');
} else if($view=='cnames') {
	$t->display('corpus_clannames.tpl');
} else if($view=='admin') { 
	$t->display('corpus_admin.tpl');
} else {
	$t->assign('heading', 'Cannot recover rfrom internal error.');
	$t->assign('message', 'Internal error logic error in corpus module - please report to BPS team!');
	$t->display('error.tpl');

}

?>
























