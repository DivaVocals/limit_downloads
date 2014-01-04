<?php
/**
 * Extra shopping Cart actions supported.
 *
 * @copyright Copyright 2009-2012 Vinos de Frutas Tropicales
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: limit_downloads.php 2012-11-26 lat9
 *
 * The main cart actions supported by the current shoppingCart class.
 *
 * This module checks for a downloadable and/or virtual product already present in the cart and disallows an additional product
 * quantities of the exactly-same configuration to be added.
 *
 * NOTE:  See also \includes\languages\english\extra_definitions\YOURTEMPLATE\limit_downloads.php for language constants.
 *
 */

switch ($_GET['action']) {
  /*---- Update product quantities in the cart ----
  */
  case 'update_product':
    if (isset($_POST['products_id'])) {
      for ($i = 0, $n=sizeof($_POST['products_id']); $i < $n; $i++) {   
        if (!(in_array($_POST['products_id'][$i], (is_array($_POST['cart_delete']) ? $_POST['cart_delete'] : array()))) && $_POST['cart_quantity'][$i] > 1 ) {
          if (isset($_POST['id'][$_POST['products_id'][$i]])) {
            foreach($_POST['id'][$_POST['products_id'][$i]] as $option => $value) {
              if (ldProductIsDownload ($_POST['products_id'][$i], $option, $value)) {
                $_POST['cart_quantity'][$i] = 1;
                $messageStack->add_session('header', sprintf(ERROR_MAXIMUM_DOWNLOADS, zen_get_products_name($_POST['products_id'][$i])), 'caution');
                break;
              }
            }
          } 
        }
      }
    }
  break;
  /*---- Add a product to cart ----
  **
  ** If a product is being added to the cart (with a quantity greater than 0) and that product includes attributes (the $_POST['id'] array
  ** is set), then if either that product is already in the cart -OR- if the quantity to be added is greater than 1,
  ** check each of the attributes being added to see if there is a download/virtual product amongst them.  If so,
  ** don't allow the duplicate download/virtual product to be added to the cart ... or just add 1 if this is the original add.
  */
  case 'add_product':
//-bof-v1.1.1c
    if (isset($_POST['products_id']) && $_POST['cart_quantity'] > 0 && isset($_POST['id']) && is_array($_POST['id'])) {
      $the_ids = $_POST['id'];
      foreach($_POST['id'] as $option => $value) {
        if (ldProductIsDownload ($_POST['products_id'], $option, $value)) {
          if ($_SESSION['cart']->in_cart(zen_get_uprid($_POST['products_id'], $the_ids))) {
            $messageStack->add_session('header', sprintf(ERROR_MAXIMUM_DOWNLOADS, zen_get_products_name($_POST['products_id'])), 'caution');
            zen_redirect(zen_href_link($goto, zen_get_all_get_params($parameters)));
            
          } elseif ($_POST['cart_quantity'] > 1) {
            $_POST['cart_quantity'] = 1;
            $messageStack->add_session('header', sprintf(ERROR_MAXIMUM_DOWNLOADS, zen_get_products_name($_POST['products_id'])), 'caution');
            
          }
          break;
        }
      }
    }
//-eof-v1.1.1c
  break;
}

function ldProductIsDownload ($products_id, $options_id, $options_value_id) {
  global $db;
//-v1.1.2d  if (zen_get_products_virtual($products_id)) return true;
  
  $sql = "SELECT count(*) as count
          FROM " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
          WHERE pa.products_id = " . (int)$products_id . "
          AND   pa.options_id = " . (int)$options_id . "
          AND   pa.options_values_id = " . (int)$options_value_id . "
          AND   pa.products_attributes_id = pad.products_attributes_id";
  $sql_result = $db->Execute($sql);

  return ($sql_result->fields['count'] > 0) ? true : false;
}