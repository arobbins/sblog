<?php

  require("../vendor/autoload.php");

  use \DrewM\MailChimp\MailChimp;

  $MailChimp = new MailChimp('255970dd153fe7b1d83bec1478cbaa74-us11');
  $result = $MailChimp->get('lists');

  // Mailing List
  // $list_id = '5c6bd183d4';

  // Simpleblend Blog
  $list_id = '53f4059701';

  $result = $MailChimp->post("lists/$list_id/members", [
    'email_address' => 'andrew@liberty.reviews',
    'status'        => 'subscribed'
  ]);

  if ($MailChimp->success()) {
    print_r($result);

  } else {
    echo $MailChimp->getLastError();

  }





  // require_once 'inc/MCAPI.class.php';
  // $api = new MCAPI('[[YOUR_API_KEY]]');
  // $merge_vars = array('FNAME'=>$_POST["fname"], 'LNAME'=>$_POST["lname"]);
  //
  // // Submit subscriber data to MailChimp
  // // For parameters doc, refer to: http://apidocs.mailchimp.com/api/1.3/listsubscribe.func.php
  // $retval = $api->listSubscribe( '[[YOUR_LIST_ID]]', $_POST["email"], $merge_vars, 'html', false, true );
  //
  // if ($api->errorCode){
  //   echo "<h4>Please try again.</h4>";
  //
  // } else {
  //   echo "<h4>Thank you, you have been added to our mailing list.</h4>";
  //
  // }

?>
