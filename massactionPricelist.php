<?php
require('config.php');
dol_include_once('pricelist/class/pricelist.class.php');
dol_include_once('pricelist/class/pricelistMassaction.class.php');
dol_include_once('pricelist/class/pricelistMassactionIgnored.class.php');
dol_include_once('abricot/includes/class/class.form.core.php');
dol_include_once('product/class/product.class.php');

if(is_file(DOL_DOCUMENT_ROOT."/lib/product.lib.php")) dol_include_once("/lib/product.lib.php");
else dol_include_once("/core/lib/product.lib.php");

global $langs,$db;
$langs->Load("other");

$pricelist = new Pricelist($db);
$pricelistMassaction = new PricelistMassaction($db);
$pricelistMassactionsIgnored = new PricelistMassactionIgnored($db);
$form = new Form($db);
$formA = new TFormCore($db);


$id = GETPOST('id','int');
$action = GETPOST('action','alpha');
$toselect = GETPOST('toselect');
$massaction=GETPOST('massaction','alpha');
$pricelistMassaction->fetch($id);
$confirm=__get('confirm','no');

/*
 *  ACTIONS
 */

// Suppression d'éléments dans la liste
if ($action == 'deleteElements' && $confirm == 'yes') {
	$TSelectedPricelist = json_decode(GETPOST('toSelectConfirm'), true);
	if (!empty($TSelectedPricelist)) {
		foreach ($TSelectedPricelist as $priceListId) {
			$pricelist->fetch($priceListId);
			$pricelist->delete($user);
		}
	}
}

// Suppression
if ($action == 'confirm_delete' && isset($id)){
	$pricelistMassaction->delete($user);
	header("Location: massactionPricelistList.php");
	exit;
}

// Edited les tableaux de données (pour ajouter les liens)
function formatArray($db,array &$TPricelist){
	$product = new Product($db);
	foreach ($TPricelist as $id => &$pricelist){
		$product->fetch($pricelist['fk_product']);
		$pricelist['product_link'] = $product->getNomUrl();
		$pricelist['product_label'] = $product->label;
	}
}

// Valid products
$TPricelists = $pricelist->getAllOfMassaction($db,$id);
formatArray($db,$TPricelists);

// Ignored products
$TPricelistsIgnored = $pricelistMassactionsIgnored->getAllByMassaction($db,$id);
formatArray($db,$TPricelistsIgnored);

/*
 * VIEW
 */

$general_propreties = array(
	'view_type' => 'list'
	, 'limit' => array(
		'nbLine' => $nbLine
	)
	, 'subQuery' => array()
	, 'link' => array()
	, 'type' => array()
	, 'search' => array()
	, 'translate' => array()

	, 'list' => array(
		'image' => 'title_products.png'
		, 'picto_precedent' => '<'
		, 'picto_suivant' => '>'
		, 'noheader' => 0
		, 'messageNothing' => $langs->trans('NoProducts')
		, 'picto_search' => img_picto('', 'search.png', '', 0)
		)
	, 'title' => array(
			'rowid' => 'ID'
			,'product_link' => $langs->trans('Product')
			,'product_label' => $langs->trans('Label')
		)
	, 'eval' => array()
);

// Modified Products
$modified_propreties = $general_propreties;
$modified_propreties['list']['title'] = $langs->trans('ModifiedProducts');
// To allow checkboxes only if not passed
if (!$pricelistMassaction->isPassed()){
	$modified_propreties['allow-fields-select'] = true;
	$modified_propreties['list']['massactions'] = array('masssactionDeletePricelistElements' => $langs->trans('Delete'));
	$modified_propreties['title']['selectedfields'] = $toselect;
}


// Ignored Products
$ignored_propreties = $general_propreties;
$ignored_propreties['list']['title'] = $langs->trans('IgnoredProducts');

// Header
llxHeader('',$langs->trans('MassactionsPricelist'),'','');

// Condifmation Suppression
if($action == 'delete' && isset($id)){
	print $form->formconfirm("massactionPricelist.php?id=".$id, $langs->trans("DeletePricelist"), $langs->trans("ConfirmDeletePricelist"), "confirm_delete", '', 0, 1);
}

// Card
print '<table class="border" width="100%">';
// Date
print '<tr>';
print '<td width="15%">'.$langs->trans("Date").'</td><td colspan="2">';
print date('d/m/Y', $pricelistMassaction->date_change);
print '</td>';
print '</tr>';
// Reason
print '<tr><td>'.$langs->trans("Reason").'</td><td>'.$pricelistMassaction->reason.'</td>';
print '</tr>';
// Changement
print '<tr><td>'.$langs->trans("Change").'</td><td>'.vatrate($pricelistMassaction->reduc,true).'</td></tr>';
// Status (already passed or not)
print '<tr><td>'.$langs->trans("Status").'</td><td>';
if ($pricelistMassaction->isPassed()){
	print $langs->trans('Passed');
}
else {
	print $langs->trans('ToCome');
}
print '</td></tr>';
print "</table>\n";

if (!$pricelistMassaction->isPassed()){
	print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;id='.$id.'">'.$langs->trans("Delete").'</a></div>';
}
else {
	print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("MassactioinPassed").'">'.$langs->trans("Delete").'</a></div>';
}

print '<div id="modifiedProducts">';
print $formA->begin_form(null,'masssactionDeletePricelistElements');
if ($massaction == 'masssactionDeletePricelistElements'){
	print '<div style="padding-top: 2em;">';
	print $formA->hidden('toSelectConfirm', dol_escape_htmltag(json_encode($toselect)));
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassDeletion"), $langs->trans("ConfirmMassDeletionQuestion", count($toselect)), "deleteElements", null, '', 0, 200, 500, 1);
	print '</div>';
}
// Valid products
$listview = new Listview($db, 'modified_products');
print $listview->renderArray($db, $TPricelists,$modified_propreties);

$formA->end();

print '</div>';
print '<div id="ignoredProducts">';

// Ignored products
$listview = new Listview($db, 'ignored_products');
print $listview->renderArray($db, $TPricelistsIgnored, $ignored_propreties);

print '<div>';

llxFooter();
$db->close();