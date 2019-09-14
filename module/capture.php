<?php 


if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) 
$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
?>
<?php


  $buyer          = 0;

  include('database_connection_FHM.php');

  // Declarations
  $url_success_s      = 'thank-you-s';
  $url_success     = 'Thank_You';
  $posted         = 0;
  $valid          = 1;
  $valid_debug      = false; // Always set to true in capture-test.php
  $output         = false; // Display error True | Continue False
  $irs_logics       = true;
  $resp           = new stdClass();
  $resp->use_proxy    = false;
  
  $cakeTaxDebt = array(
	'$7,500 to $9,999' => '8750',
	'$10,000 to $14,999' => '12500',
	'$15,000 to $19,999' => '17500',
	'$20,000 to $24,999' => '22500',
	'$25,000 to $29,999' => '27500',
	'$30,000 to $39,999' => '35000',
	'$40,000 to $59,999' => '50000',
	'$60,000 to $79,999' => '70000',
	'$80,000 to $99,999' => '90000',
	'$100,000+' => '100000',
  );

  // Get Trusted Form Cert for All Leads -- 2016-08-24 -- MAM
  if (isset($Scrape) && FALSE) {
    $Scrape->fetch($token_url);
    $token_response = $Scrape->result;
    $token_key = $Scrape->fetchBetween('b.src="https://api.trustedform.com/', '/er.js', $token_response);
    $token_cert_url = 'https://cert.trustedform.com/'.$token_key;
  } else {
    $token_cert_url = $_REQUEST['trustedformcerturl'];
  }
  
  if ($_GET)
    $_POST = $_GET;

  $phone_home = $phone_area.$phone_pre.$phone_number;
  $phone_work = $work_area.$work_pre.$work_number;
  $lead_domain = 'www.backtaxeshelp.com';
  $lead_domain = (!isset($lead_domain)) ? 'www.backtaxeshelp.com' : $lead_domain;

  // Handle Tags
  if (strpos($_SERVER['HTTP_REFERER'], 'backtaxeshelp')>0) {
    # On BTH
    # Add Session Tags to Post
    post_tagging();
  } else {
    # Do Nothing
  }

  unset($_POST['buyer']); // Remove from Posted Variables
  unset($_POST['lead_domain']);
  unset($_GET['buyer']); // Remove from Querystring Variables
  unset($_GET['lead_domain']);
  
  // Unset buyer if buyer2 and not debt range
  if ($tax_debt_amount_c != "$7,500 to $9,999" && $buyer=="2") 
    $buyer = "0";

  // Unset buyer if buyer1 and buyer2 are off
  if (!$settings['buyer1'] && !$settings['buyer2'])
    $buyer = "0";

  # Get Buyer Details
  if ($buyer>0) {
    // Get Buyer
    $sql = "SELECT * FROM tbl_buyer WHERE buyer_index=".$buyer.";";
    $row = $db->fetch_row($sql);
    // Set Buyer Details
    $buyer        = $row['buyer_index'];
    $buyerID      = $row['buyerID'];
    $buyer_firstname  = $row['buyer_firstname'];
    $buyer_lastname   = $row['buyer_lastname'];
    $buyer_officername  = $row['buyer_firstname']." ".$row['buyer_lastname'];
    $buyer_email    = explode(",", $row['buyer_email']);
    $buyer_crm      = $row['buyer_crm'];
    $buyer_lead_id    = $row['buyer_lead_provider_id']; 
    $url_success    = ($row['buyer_thankyou']!='') ? $row['buyer_thankyou'] : $url_success;
    $url_error      = ($row['buyer_error']!='') ? $row['buyer_error'] : $url_error;
    $lead_domain    = ($row['buyer_domain']!='') ? $row['buyer_domain'] : $lead_domain;
  }
  
  // Valid Tests
  if ($valid) {
	  $cErr = '';
    // Get Amount Owed Details
    $TaxAmount = $db->fetch_all_array('SELECT amount_owed FROM tbl_amount ORDER BY amount_id ASC;');
    foreach($TaxAmount as $row) { $owed[] = $row['amount_owed']; }
    $a = explode(' ', strtolower($lead_notes_c));
    // Testing for Valid Phone #
    $valid = (array_key_exists($phone_area, $phonelist)) ? $valid : 0; // Check for valid Area Code
	if (0 == $valid) {
		$cErr = 'Check for valid Area Code;';
	}
    // Testing for spaces in Lead Notes
    $valid = ($lead_notes_c!='' && strpos(trim($lead_notes_c), " ")==0) ? 0 : $valid; // Check for spaces in Lead Notes
	if (0 == $valid) {
		$cErr .= ' Check for spaces in Lead Notes;';
	}
    // Testing for Valid Amounts
    $valid = (in_array($tax_debt_amount_c, $owed)) ? $valid : 0; // Check for Valid Amount
	if (0 == $valid) {
		$cErr .= ' Check for Valid Amount;';
	}
    // Testing for Invalid Words
    foreach ($words as $word) {
      if ((in_array(strtolower($word), $a))) {
        $valid = 0;
        break;
      }
    }
  }

  
  // Process
  if (isset($tax_debt_amount_c) && $valid) {
    // Sweep Submission for Spam
    $clean = sanitize();
    $remote_post = (isset($remote_post)) ? $remote_post : 0;
    $db_response = -1;

    // Spam Bot Test
    if ($clean) {
      // Check for duplicate email address
      $sql = "SELECT lead_id, post_date FROM tbl_leads WHERE lcase(email1)='".strtolower($email1)."' AND phone_home='".$phone_home."' ORDER BY lead_id DESC;";
      $cnt = $db->fetch_all_array($sql);
      $now = time();
      $time = strtotime($cnt[0]['post_date'])+3660;
      if ($cnt[0]['lead_id']>0) {
        $new = ($time>$now) ? 0 : 1;
      } else {
        $new = 1;
      }

      // Bypassing for iTechStat.com for testing purposes
      $new = (strpos($email1, 'itechstat.com')>0 || $valid_debug || strpos($email1, 'test.com')>0) ? 1 : $new;
      
      // Handle Acknowledgement Quote //
      if ($qack) {
        $lead_notes_c .= $quotestr;
        $_POST['lead_notes_c'] .= $quotestr;
      }
      ########## Acknowledgement #########

      // Write to database
      if ($new) {

        // Write from Remote Post
        if ($remote_post==1) {
          
          
          $sqlins = array (
            'user'=>$user,
            'first_name'=>$first_name,
            'last_name'=>$last_name,
            'phone_home'=>$phone_home,
            'phone_work'=>$phone_work,
            'email1'=>$email1,
            'lead_source'=>$_POST['lead_source'],
            'tax_debt_amount_c'=>$tax_debt_amount_c,
            'lead_notes_c'=>$lead_notes_c,
            'primary_address_street'=>$primary_address_street,
            'primary_address_city'=>$primary_address_city,
            'primary_address_state'=>$primary_address_state,
            'primary_address_postalcode'=>$primary_address_postalcode,
            'portal_name'=>$clientipaddress,
            'opportunity_name'=>$opportunity_name,
            'Description'=>$Description,
            'assigned_user_id'=>$assigned_user_id,
            'result_code_c'=>$result_code_c,
            'lead_source_description'=>$_POST['lead_source_description'],
            'tax_type_c'=>$tax_type_c,
            'tax_agency_c'=>$tax_agency_c,
            'user_id'=>$assigned_user_id,
            'post_date'=>date('Y-m-d H:i', time()),
            'type_id'=>$type_id,
            'lead_site'=>$_POST['lead_site'],
            'buyer'=>$buyer,
            'roundrobin'=>$roundrobin,
            'utm_medium'=>$utm_medium, 
            'utm_source'=>$utm_source, 
            'utm_content'=>$utm_content, 
            'utm_campaign'=>$utm_campaign, 
            'utm_term'=>$utm_term, 
            'kw'=>$kw, 
            'matchtype'=>$matchtype, 
            'network'=>$network, 
            'loc'=>$loc, 
            'ad'=>$ad, 
            'site'=>$site, 
            'device'=>$device, 
            'adpos'=>$adpos, 
            '_param1'=>$_param1, 
            '_param2'=>$_param2, 
            '_param3'=>$_param3, 
            'sitelink'=>$sitelink, 
            'referrer'=>urlencode($referrer), 
            'lastpage'=>urlencode($lastpage)
          );
        // Write from local Domain
        
        
        } else {
          
          
          $sqlins = array (
            'user'=>$user,
            'first_name'=>$first_name,
            'last_name'=>$last_name,
            'phone_home'=>$phone_home,
            'phone_work'=>$phone_work,
            'email1'=>$email1,
            'lead_source'=>$lead_source,
            'tax_debt_amount_c'=>$tax_debt_amount_c,
            'lead_notes_c'=>$lead_notes_c,
            'primary_address_street'=>$primary_address_street,
            'primary_address_city'=>$primary_address_city,
            'primary_address_state'=>$primary_address_state,
            'primary_address_postalcode'=>$primary_address_postalcode,
            'portal_name'=>$clientipaddress,
            'opportunity_name'=>$opportunity_name,
                  'Description'=>$Description,
            'assigned_user_id'=>$assigned_user_id,
            'result_code_c'=>$result_code_c,
            'lead_source_description'=>$lead_source_description,
            'tax_type_c'=>$tax_type_c,
            'tax_agency_c'=>$tax_agency_c,
            'user_id'=>$assigned_user_id,
            'post_date'=>date('Y-m-d H:i', time()),
            'type_id'=>$type_id,
            'lead_site'=>$lead_site,
            'buyer'=>$buyer,
            'roundrobin'=>$roundrobin,
            'utm_medium'=>$utm_medium, 
            'utm_source'=>$utm_source, 
            'utm_content'=>$utm_content, 
            'utm_campaign'=>$utm_campaign, 
            'utm_term'=>$utm_term, 
            'kw'=>$kw, 
            'matchtype'=>$matchtype, 
            'network'=>$network, 
            'loc'=>$loc, 
            'ad'=>$ad, 
            'site'=>$site, 
            'device'=>$device, 
            'adpos'=>$adpos, 
            '_param1'=>$_param1, 
            '_param2'=>$_param2, 
            '_param3'=>$_param3, 
            'sitelink'=>$sitelink, 
            'referrer'=>urlencode($referrer), 
            'lastpage'=>urlencode($lastpage)
          );
        
        
        }

        // Perform Insert
        if (!$valid_debug) {
			file_put_contents(__DIR__ . '/my_log.txt', print_r($sqlins,1) . PHP_EOL . PHP_EOL, FILE_APPEND);
			$db_response = $db->query_insert('tbl_leads', $sqlins);
			// var_dump($db_response);
		}

        // Build for Post to URL
        $post_string = "";
        // Test for Lite
        if ($_POST['lead_source']!='Tomato_Text_Lite' || $_POST['lead_source']!='Tomato_Text' ) {
          
          if ($tax_debt_amount_c == "$7,500 to $9,999") 
            $_POST['lead_source'] = "Tomato_Text_Lite";
          else 
            $_POST['lead_source'] = "Tomato_Text"; 
        
        }
        // Adding trustedform_cert_url for new remote posting -- 2016-08-22 -- MAM
        $post_string .= '&trustedform_cert_url='.UrlEncode($token_cert_url)."&";

        // Cycle Post Variables -- 2016-08-24 -- MAM
#       foreach(array_keys($_POST) AS $key) {
#         $post_string .= ($key!='lead_source_description' && $key!='lead_domain') ? "$key=". UrlEncode($_POST[$key]). "&" : "" ;
#       }
        $forbidden_keys = array(
          'lead_source_description',
          'lead_domain',
          'lastpage',
          'referrer',
          'url_success', 
          'url_error', 
          'qack', 
          'trustedformcerturl',
          'tax_debt_amount_c',
          '_param1',
          '_param2',
          '_param3',
          'phone_area',
          'phone_pre', 
          'phone_number',
          'email1',
          'tax_agency_c',
          'path',
          'image_x',
          'image_y',
          'utm_medium',
          'utm_source',
          'utm_content',
          'utm_campaign',
          'utm_term',
          'kw',
          'matchtype',
          'network',
          'loc',
          'ad',
          'site',
          'device',
          'adpos',
          'sitelink',
          'lead_source',
#         'lead_notes_c',
#         'lead_site',
#         'type_id',
#         'primary_address_postalcode',
          'xxTrustedFormToken'      
        );
        foreach(array_keys($_POST) AS $key) {
          $post_string .= (!in_array($key, $forbidden_keys)) ? "$key=". UrlEncode($_POST[$key]). "&" : "" ;
        } 
        // Build Phone Numbers
#       $post_string .= "phone_home=".$phone_home."&phone_work=".$phone_work."&";
        $post_string .= "phone_1=".$phone_home."&phone_2=".$phone_home."&email=".$email1."&tdn_tax_debt_amount=".$tax_debt_amount_c."&comments=".$_POST['lead_notes_c'].'&tdn_tax_agency='.$tax_agency_c."&tdn_lead_source_description=".$_POST['lead_source'];
        // add ip_address
        $post_string .= "&ip_address={$clientipaddress}";
        // Error Message
        $cache = 'Successful Test and submitted to the database';
		
		$post_string .='&lead_source=' . $_POST['lead_source'];
		$post_string .='&email1=' . $_POST['email1'];
		$post_string .='&tax_debt_amount_c=' . $cakeTaxDebt[$tax_debt_amount_c];
		$post_string .='&tax_agency_c=' . $tax_agency_c;
		$post_string .='&lead_source_description=' . $_POST['lead_source_description'];	


		
		// Post to Cake
		$ch = curl_init('https://lafires.com/d.ashx?ckm_campaign_id=5&ckm_key=ku5iHOp9NJc&');
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string); 
          curl_setopt($ch, CURLINFO_HEADER_OUT, true);
          curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
          curl_setopt($ch, CURLOPT_MAXREDIRS, 1);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
          curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		  curl_setopt($ch, CURLOPT_TIMEOUT, 1);
          curl_setopt($ch, CURLOPT_URL, 'https://lafires.com/d.ashx?ckm_campaign_id=5&ckm_key=ku5iHOp9NJc&');
          curl_setopt($ch, CURLOPT_VERBOSE, 1);
          curl_setopt($ch, CURLOPT_ENCODING, '');
          ob_start();
            $cache = curl_exec ($ch);
          ob_end_clean();
          curl_close ($ch);
		  

		  
		
/*
		$xml = new SimpleXMLElement($cache);
		
		if ( 0 == $xml->code ) {
			$db->query_update('tbl_leads', [
                'external_lead_id' => $xml->leadid,
              ], "lead_id = " . $db_response);
		} else {
			$cake_errors = '';
			foreach ($xml->errors as $element) {
			  $cake_errors .= $element->error . '; ';
			}
			$db->query_update('tbl_leads', [
                'external_lead_id' => $cake_errors,
              ], "lead_id = " . $db_response);
		}
*/
		$posted = 1;
	
		
        // Build Email Message
        $body = "The following details were submitted online @ " . date('m-d-Y H:i', time()) . "<br><br>
                Name : ".$first_name." ".$last_name."<br>
                Email : ".$email1."<br>
                Home Phone : ".$phone_home."<br>
                Work Phone : ".$phone_work."<br>
                City : ".$primary_address_city."<br>
                State : ".$primary_address_state."<br><br>
                Tax Owed : ".$tax_debt_amount_c."<br>
                Tax Owed to : ".$tax_agency_c."<br>
                Tax Problem : ".$lead_notes_c."<br><br>
                IP Address : ".$_POST['clientipaddress']."<br>
                Lead Source : ".$_POST['lead_source']."<br>
                Assigned User ID : ".$_POST['assigned_user_id']."<br><br>
                Validity : ".$error."<br><br>
        ";
        // Send Email to Admin
        if ($_SERVER['HTTP_HOST']!='localhost') {   
          mysend_mail($dflt_email, $dflt_email, 'Lead Submission ', $body);
        }

        // Send if Buyer
        if ($buyer) {
          // Build Email
          $body = "The following details were submitted online @ " . date('m-d-Y H:i', time()) . "<br><br>
                First Name : ".$first_name." <br>
                Last Name : ".$last_name."<br>
                Email : ".$email1."<br>
                Zip Code : ".$primary_address_postalcode."<br>
                Primary Phone : ".$phone_home."<br>
                Secondary Phone : ".$phone_work."<br>
                Tax Debt Amount : ".$tax_debt_amount_c."<br>
                Tax Agency : ".$tax_agency_c."<br>
                Tax Problem : ".$lead_notes_c."<br>";
          // Send to Leads1
          if ($buyer=="1" && $settings['buyer1']) {
            mysend_mail('leads1@taxdebtleads.net', $dflt_email, 'TaxDebtLeads.net Lead', $body); // Email Customer
#           mysend_mail('allen.messenger@itechstat.com', $dflt_email, 'TaxDebtLeads.net Lead', $body); // Email Customer
            $url_success = $settings['buyer1success'];
            $url_error = $settings['buyer1error'];
          } else if ($buyer=="2" && $settings['buyer2']) {
            mysend_mail('d.bayla@mutuallawgroup.com', 'admin@taxdebtleads.net', 'TaxDebtLeads.net 2 Lead', $body); // Email Customer
#           mysend_mail('allen.messenger@itechstat.com', $dflt_email, 'TaxDebtLeads.net 2 Lead', $body); // Email Customer
            $url_success = $settings['buyer2success'];
            $url_error = $settings['buyer2error'];
          }
        } 
      }
      // Error Message
      $error = 'Successfully Captured!';
    } else {
      // Let Spammers believe they were successful
      // Do not write to database
      // Forward information to admin
      $error = 'Suspected SPAM!!!';
    }

    // Echo Error Messages
    if ($valid_debug && $output) {
      echo $error;
      exit;
    }
    // If posted to Remote Server, use redirect page
    // else redirect to fake redirect page
    $url_success = ($posted) ? $url_success : $url_success_s;
    // Redirect 
	
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, POST');
	header("Access-Control-Allow-Headers: X-Requested-With");

    if ($clean) {
      unset($_POST);
	  if (isset($ajax)) {
		echo '{"redirect": "/'.$url_success . '.html"}';
	  } else {
		header('location: https://'.$server.$root. /*'/'.*/ $url_success . '.html');
	  }
    } else {
		if (isset($ajax)) {
			echo '{"redirect": "/'.$url_error.'"}';
		} else {
			header('location: https://'.$server.$root.'/'.$url_error);
		}
    }
    exit;
  } elseif (!$valid) {
    // Build Submission Array
    $sqlins = array (
      'user'=>$user,
      'first_name'=>$first_name,
      'last_name'=>$last_name,
      'phone_home'=>$phone_home,
      'phone_work'=>$phone_work,
      'email1'=>$email1,
      'lead_source'=>$lead_source,
      'tax_debt_amount_c'=>$tax_debt_amount_c,
      'lead_notes_c'=>$lead_notes_c,
      'primary_address_street'=>$primary_address_street,
      'primary_address_city'=>$primary_address_city,
      'primary_address_state'=>$primary_address_state,
      'primary_address_postalcode'=>$primary_address_postalcode,
      'portal_name'=>$clientipaddress,
      'opportunity_name'=>$opportunity_name,
      'Description'=>$Description,
      'assigned_user_id'=>$assigned_user_id,
      'result_code_c'=>$result_code_c,
      'lead_source_description'=>$lead_source_description,
      'tax_type_c'=>$tax_type_c,
      'tax_agency_c'=>$tax_agency_c,
      'user_id'=>$assigned_user_id,
      'post_date'=>date('Y-m-d H:i', time()),
      'type_id'=>$type_id,
      'lead_site'=>$lead_site, 
      'buyer'=>$buyer
    );
    // Construct Body
    $body = "The following details were submitted online @ " . date('m-d-Y H:i', time()) . "<br><br>";
    // Append Submission Array to Body
    foreach($sqlins as $k=>$v) {
      $body .= "<div style='float: left; width: 220px'>".ucwords(str_replace('_' , ' ', $k))."</div><div style='float: left;'> = ".$v."</div>".br;
    }
    // Email
    if ($_SERVER['HTTP_HOST']!='localhost') {   
      mysend_mail($dflt_email, $dflt_email, 'Lead Submission Validation', $body);
    }
    $error = 'There was a problem with the submission and an email was sent';
    if ($valid_debug && $output) {
      echo $error;
      exit;
    }
	
	/*
	$body = 'You entered incorrect data on the site backtaxeshelp.com.';
	mysend_mail($email1, $dflt_email, 'Backtaxeshelp.com Validation', $body);
	*/
	if (isset($ajax)) {
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: GET, POST');
		header("Access-Control-Allow-Headers: X-Requested-With");
		header("Access-Control-Allow-Headers: X-Requested-With");
		echo '{"redirect": "/'.$url_success_s . '.html"}';
	} else {
		header('location: https://'.$server.$root.'/'.$url_success_s . '.html');
	}
    exit;
  } else {
    // Hack attempt 
    // Just end
    exit;
  }

// Build Selections
$tax_debt_amount_c = (isset($owed)) ? $owed : $tax_debt_amount_c;
$search['tax_debt_amount_c'] = $db->fetch_selectbox($sql_amount, 'tax_debt_amount_c', $tax_debt_amount_c);
$search['tax_agency_c'] = $db->fetch_selectbox($sql_agency, 'tax_agency_c', $tax_agency_c);
$search['state'] = $db->fetch_selectbox($sql_state, 'primary_address_state', $state);

$_form = (!isset($_form)) ? 'block' : $_form;
include('capture_form_'.$_form.'.html');

?>
