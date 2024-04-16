<?php
session_start();
error_reporting(E_ERROR | E_PARSE);
date_default_timezone_set('Europe/Berlin');
require_once("captcha/AntiSpam.php");
$q = AntiSpam::getRandomQuestion();
header('Content-type: text/html; charset=utf-8');


#########################################################################
#	Kontaktformular.com         					                                #
#	http://www.kontaktformular.com        						                    #
#	All rights by KnotheMedia.de                                    			#
#-----------------------------------------------------------------------#
#	I-Net: http://www.knothemedia.de                            					#
#########################################################################
// Der Copyrighthinweis darf NICHT entfernt werden!


  $script_root = substr(__FILE__, 0,
                        strrpos(__FILE__,
                                DIRECTORY_SEPARATOR)
                       ).DIRECTORY_SEPARATOR;

$remote = getenv("REMOTE_ADDR");

function encrypt($string, $key) {
	$result = '';
	for($i=0; $i<strlen($string); $i++) {
	   $char = substr($string, $i, 1);
	   $keychar = substr($key, ($i % strlen($key))-1, 1);
	   $char = chr(ord($char)+ord($keychar));
	   $result.=$char;
	}
	return base64_encode($result);
}

@require('config.php');
require_once("captcha/AntiSpam.php");
include("PHPMailer/Secureimage.php");
// form-data should be deleted
if (isset($_POST['delete']) && $_POST['delete']){
	unset($_POST);
}

// form has been sent
if (isset($_POST["kf-km"]) && $_POST["kf-km"]) {

	// clean data
	$name		= stripslashes($_POST["name"]);
	$email		= stripslashes($_POST["email"]);
	$betreff   	= stripslashes($_POST["betreff"]);
	$nachricht  = stripslashes($_POST["nachricht"]);
	if($cfg['DATENSCHUTZ_ERKLAERUNG']) { $datenschutz = stripslashes($_POST["datenschutz"]); }
	if($cfg['Sicherheitscode']){
		$sicherheits_eingabe = encrypt($_POST["sicherheitscode"], "8h384ls94");
		$sicherheits_eingabe = str_replace("=", "", $sicherheits_eingabe);
	}

	$date = date("d.m.Y | H:i");
	$ip = $_SERVER['REMOTE_ADDR'];
	$UserAgent = $_SERVER["HTTP_USER_AGENT"];
	$host = getHostByAddr($remote);


	// formcheck	
	if(!$name) {
		$fehler['name'] = "<span class='errormsg'>Geben Sie bitte Ihren <strong>Namen</strong> ein.</span>";
	}
	
	if (!preg_match("/^[0-9a-zA-ZÄÜÖ_.-]+@[0-9a-z.-]+\.[a-z]{2,6}$/", $email)) {
		$fehler['email'] = "<span class='errormsg'>Geben Sie bitte Ihre <strong>E-Mail-Adresse</strong> ein.</span>";
	}
	
	if(!$betreff) {
		$fehler['betreff'] = "<span class='errormsg'>Geben Sie bitte einen <strong>Betreff</strong> ein.</span>";
	}
	
	if(!$nachricht) {
		$fehler['nachricht'] = "<span class='errormsg'>Geben Sie bitte eine <strong>Nachricht</strong> ein.</span>";
	}
	
	
	
	// -------------------- SPAMPROTECTION ERROR MESSAGES START ----------------------
	if($cfg['Sicherheitscode'] && $sicherheits_eingabe != $_SESSION['captcha_spam']){
		unset($_SESSION['captcha_spam']);
		$fehler['captcha'] = "<span class='errormsg'>Der <strong>Sicherheitscode</strong> wurde falsch eingegeben.</span>";
	} 
		

  if($cfg["Sicherheitsfrage"]){
	$answer = AntiSpam::getAnswerById(intval($_POST["q_id"]));
	if(isset($_POST["q"]) && $_POST["q"] != $answer){
		$fehler['q_id12'] = "<span class='errormsg'>Bitte die <strong>Sicherheitsfrage</strong> richtig beantworten.</span>";
	}
  }



	if($cfg['Honeypot'] && (!isset($_POST["mail"]) || ''!=$_POST["mail"])){
		$fehler['Honeypot'] = "<span class='errormsg' style='display: block; color:red;font-size:.75rem;'>Es besteht Spamverdacht. Bitte überprüfen Sie Ihre Angaben.</span>";
	}
	
	if($cfg['Zeitsperre'] && (!isset($_POST["chkspmtm"]) || ''==$_POST["chkspmtm"] || '0'==$_POST["chkspmtm"] || (time() - (int) $_POST["chkspmtm"]) < (int) $cfg['Zeitsperre'])){
		$fehler['Zeitsperre'] = "<span class='errormsg' style='display: block; color:red;font-size:.75rem;'>Bitte warten Sie einige Sekunden, bevor Sie das Formular erneut absenden.</span>";
	}
	
	if($cfg['Klick-Check'] && (!isset($_POST["chkspmkc"]) || 'chkspmhm'!=$_POST["chkspmkc"])){
		$fehler['Klick-Check'] = "<span class='errormsg' style='display: block; color:red;font-size:.75rem;'>Sie müssen den Senden-Button mit der Maus anklicken, um das Formular senden zu können.</span>";
	}
	
	if($cfg['Links'] < preg_match_all('#http(s?)\:\/\/#is', $nachricht, $irrelevantMatches)){
		$fehler['Links'] = "<span class='errormsg' style='display: block; color:red;font-size:.75rem;'>Ihre Nachricht darf ".(0==$cfg['Links'] ? 
																																'keine Links' : 
																																(1==$cfg['Links'] ? 
																																	'nur einen Link' : 
																																	'maximal '.$cfg['Links'].' Links'
																																)
																															)." enthalten.</span>";
	}
	
	if(''!=$cfg['Badwordfilter'] && 0!==$cfg['Badwordfilter'] && '0'!=$cfg['Badwordfilter']){
		$badwords = explode(',', $cfg['Badwordfilter']);			// the configured badwords
		$badwordFields = explode(',', $cfg['Badwordfields']);		// the configured fields to check for badwords
		$badwordMatches = array();									// the badwords that have been found in the fields
		
		if(0<count($badwordFields)){
			foreach($badwords as $badword){
				$badword = trim($badword);												// remove whitespaces from badword
				$badwordMatch = str_replace('%', '', $badword);							// take human readable badword for error-message
				$badword = addcslashes($badword, '.:/');								// make ., : and / preg_match-valid
				if('%'!=substr($badword, 0, 1)){ $badword = '\\b'.$badword; }			// if word mustn't have chars before > add word boundary at the beginning of the word
				if('%'!=substr($badword, -1, 1)){ $badword = $badword.'\\b'; }			// if word mustn't have chars after > add word boundary at the end of the word
				$badword = str_replace('%', '', $badword);								// if word is allowed in the middle > remove all % so it is also allowed in the middle in preg_match 
				foreach($badwordFields as $badwordField){
					if(preg_match('#'.$badword.'#is', $_POST[trim($badwordField)]) && !in_array($badwordMatch, $badwordMatches)){
						$badwordMatches[] = $badwordMatch;
					}
				}
			}		
			
			if(0<count($badwordMatches)){
				$fehler['Badwordfilter'] = "<span class='errormsg' style='display: block; color:red;font-size:.75rem;'>Folgende Begriffe sind nicht erlaubt: ".implode(', ', $badwordMatches)."</span>";
			}
		}		
	}
  // -------------------- SPAMPROTECTION ERROR MESSAGES ENDE ----------------------
  
  
	if($cfg['DATENSCHUTZ_ERKLAERUNG'] && isset($datenschutz) && $datenschutz == ""){ 
		$fehler['datenschutz'] = "<span class='errormsg'>Sie müssen die <strong>Datenschutz&shy;erklärung</strong> akzeptieren.</span>";
	}

	// there are NO errors > upload-check
    if (!isset($fehler) || count($fehler) == 0) {
      $error             = false;
      $errorMessage      = '';
      $uploadErrors      = array();
      $uploadedFiles     = array();
      $totalUploadSize   = 0;
	  $j = 0;
	  
	  
	  if (2==$cfg['UPLOAD_ACTIVE'] && in_array($_SERVER['REMOTE_ADDR'], $cfg['BLACKLIST_IP']) === true) {
          $error = true;
		  $uploadErrors[$j]['name'] = '';
          $uploadErrors[$j]['error'] = "Sie haben keine Erlaubnis Dateien hochzuladen.";
          $j++;
      }

      

      if (!$error) {
          for ($i=0; $i < $cfg['NUM_ATTACHMENT_FIELDS']; $i++) {
              if ($_FILES['f']['error'][$i] == UPLOAD_ERR_NO_FILE) {
                  continue;
              }

              $extension = explode('.', $_FILES['f']['name'][$i]);
              $extension = strtolower($extension[count($extension)-1]);
              $totalUploadSize += $_FILES['f']['size'][$i];

              if ($_FILES['f']['error'][$i] != UPLOAD_ERR_OK) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  switch ($_FILES['f']['error'][$i]) {
                      case UPLOAD_ERR_INI_SIZE :
                          $uploadErrors[$j]['error'] = 'Die Datei ist zu groß (PHP-Ini Direktive).';
                      break;
                      case UPLOAD_ERR_FORM_SIZE :
                          $uploadErrors[$j]['error'] = 'Die Datei ist zu groß (MAX_FILE_SIZE in HTML-Formular).';
                      break;
                      case UPLOAD_ERR_PARTIAL :
						  if (2==$cfg['UPLOAD_ACTIVE']) {
                          	  $uploadErrors[$j]['error'] = 'Die Datei wurde nur teilweise hochgeladen.';
						  } else {
							  $uploadErrors[$j]['error'] = 'Die Datei wurde nur teilweise versendet.';
					  	  }
                      break;
                      case UPLOAD_ERR_NO_TMP_DIR :
                          $uploadErrors[$j]['error'] = 'Es wurde kein temporärer Ordner gefunden.';
                      break;
                      case UPLOAD_ERR_CANT_WRITE :
                          $uploadErrors[$j]['error'] = 'Fehler beim Speichern der Datei.';
                      break;
                      case UPLOAD_ERR_EXTENSION  :
                          $uploadErrors[$j]['error'] = 'Unbekannter Fehler durch eine Erweiterung.';
                      break;
                      default :
						  if (2==$cfg['UPLOAD_ACTIVE']) {
                          	  $uploadErrors[$j]['error'] = 'Unbekannter Fehler beim Hochladen.';
						  } else {
							  $uploadErrors[$j]['error'] = 'Unbekannter Fehler beim Versenden des Email-Attachments.';
						  }
                  }

                  $j++;
                  $error = true;
              }
              if ($totalUploadSize > $cfg['MAX_ATTACHMENT_SIZE']*1024) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'Maximaler Upload erreicht ('.$cfg['MAX_ATTACHMENT_SIZE'].' KB).';
                  $j++;
                  $error = true;
              }
              if ($_FILES['f']['size'][$i] > $cfg['MAX_FILE_SIZE']*1024) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'Die Datei ist zu groß (max. '.$cfg['MAX_FILE_SIZE'].' KB).';
                  $j++;
                  $error = true;
              }
              if (!empty($cfg['WHITELIST_EXT']) && strpos($cfg['WHITELIST_EXT'], $extension) === false) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'Die Dateiendung ist nicht erlaubt.';
                  $j++;
                  $error = true;
              }
              if (preg_match("=^[\\:*?<>|/]+$=", $_FILES['f']['name'][$i])) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'Ungültige Zeichen im Dateinamen (\/:*?<>|).';
                  $j++;
                  $error = true;
              }
              if (2==$cfg['UPLOAD_ACTIVE'] && file_exists($cfg['UPLOAD_FOLDER'].'/'.$_FILES['f']['name'][$i])) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'Die Datei existiert bereits. Bitte benennen Sie die Datei um.';
                  $j++;
                  $error = true;
              }
              if(!$error) {
				  if (2==$cfg['UPLOAD_ACTIVE']) {
                     move_uploaded_file($_FILES['f']['tmp_name'][$i], $cfg['UPLOAD_FOLDER'].'/'.$_FILES['f']['name'][$i]);
				  }
                  $uploadedFiles[$_FILES['f']['tmp_name'][$i]] = $_FILES['f']['name'][$i];
              }
          }
      }

      if ($error) {
          $errorMessage = 'Es sind folgende Fehler beim Versenden des Kontaktformulars aufgetreten:'."\n";
          if (count($uploadErrors) > 0) {
              $tmp = '';
			  foreach ($uploadErrors as $err) {
                  $tmp .= '<strong>'.$err['name']."</strong><br/>\n- ".$err['error']."<br/><br/>\n";
              }
              $tmp = "<br/><br/>\n".$tmp;
          }
          $errorMessage .= $tmp.'';
          $fehler['upload'] = "<span class='errormsg' style='display: block;'>".$errorMessage."</span>";
      }
	}


	// there are NO errors > send mail
   if (!isset($fehler))
   {
		// ------------------------------------------------------------
		// -------------------- send mail to admin --------------------
		// ------------------------------------------------------------

		// ---- create mail-message for admin
	 $mailcontent  = "Folgendes wurde am ". $date ." Uhr per Formular geschickt:\n" . "-------------------------------------------------------------------------\n\n";
   $mailcontent .= "Name: " . $name . "\n";
   $mailcontent .= "E-Mail: " . $email . "\n";
   $mailcontent .= "\nBetreff: " . $betreff . "\n";
   $mailcontent .= "Nachricht:\n" . $nachricht = preg_replace("/\r\r|\r\n|\n\r|\n\n/","\n",$nachricht) . "\n\n";
		if(count($uploadedFiles) > 0){
			if(2==$cfg['UPLOAD_ACTIVE']){
				$mailcontent .= "\n\n";
				$mailcontent .= 'Es wurden folgende Dateien hochgeladen:'."\n";
				foreach ($uploadedFiles as $filename) {
					$mailcontent .= ' - '.$cfg['DOWNLOAD_URL'].'/'.$cfg['UPLOAD_FOLDER'].'/'.$filename."\n";
				}
			} else {
				$mailcontent .= "\n\n";
				$mailcontent .= 'Es wurden folgende Dateien übertragen:'."\n";
				foreach ($uploadedFiles as $filename) {
					$mailcontent .= ' - '.$filename."\n";
				}
			}
		}
		if($cfg['DATENSCHUTZ_ERKLAERUNG']) { $mailcontent .= "\n\nDatenschutz: " . $datenschutz . " \n"; }
    $mailcontent .= "\n\nIP Adresse: " . $ip . "\n";
		$mailcontent = strip_tags ($mailcontent);

		// ---- get attachments for admin
		$attachments = array();
		if(1==$cfg['UPLOAD_ACTIVE'] && count($uploadedFiles) > 0){
			foreach($uploadedFiles as $tempFilename => $filename) {
				$attachments[$filename] = file_get_contents($tempFilename);
			}
		}

		$success = false;

        // ---- send mail to admin
        if($smtp['enabled'] !== 0) {
            require_once __DIR__ . '/smtp.php';
            $success = SMTP::send(
                $smtp['host'],
                $smtp['user'],
                $smtp['password'],
                $smtp['encryption'],
                $smtp['port'],
                $email,
                $ihrname,
                $empfaenger,
                $betreff,
                $mailcontent,
                (2==$cfg['UPLOAD_ACTIVE'] ? array() : $uploadedFiles),
                $cfg['UPLOAD_FOLDER'],
                $smtp['debug']
            );
        } else {
            $success = sendMyMail($email, $name, $empfaenger, $betreff, $mailcontent, $attachments);
        }

    	// ------------------------------------------------------------
    	// ------------------- send mail to customer ------------------
    	// ------------------------------------------------------------
    	if(
			$success && 
			(
				2==$cfg['Kopie_senden'] || 																// send copy always
				(1==$cfg['Kopie_senden'] && isset($_POST['mail-copy']) && 1==$_POST['mail-copy'])		// send copy only if customer want to
			)
		){

    		// ---- create mail-message for customer
			$mailcontent  = "Vielen Dank für Ihre E-Mail. Wir werden schnellstmöglich darauf antworten.\n\n";
			$mailcontent .= "Zusammenfassung: \n" .

  "-------------------------------------------------------------------------\n\n";

   $mailcontent .= "Name: " . $name . "\n";
   $mailcontent .= "E-Mail: " . $email . "\n";
   $mailcontent .= "\nBetreff: " . $betreff . "\n";
   $mailcontent .= "Nachricht:\n" . str_replace("\r", "", $nachricht) . "\n\n";
    		if(count($uploadedFiles) > 0){
    			$mailcontent .= 'Sie haben folgende Dateien übertragen:'."\n";
    			foreach($uploadedFiles as $file){
    				$mailcontent .= ' - '.$file."\n";
    			}
    		}
    		$mailcontent = strip_tags ($mailcontent);

    		// ---- send mail to customer
            if($smtp['enabled'] !== 0) {
                SMTP::send(
                    $smtp['host'],
                    $smtp['user'],
                    $smtp['password'],
                    $smtp['encryption'],
                    $smtp['port'],
                    $empfaenger,
                    $ihrname,
                    $email,
                    "Ihre Anfrage",
                    $mailcontent,
                    array(),
                    $cfg['UPLOAD_FOLDER'],
                    $smtp['debug']
                );
            } else {
                $success = sendMyMail($empfaenger, $ihrname, $email, "Ihre Anfrage", $mailcontent);
            }
		}
		
		// redirect to success-page
		if($success){
			if($smtp['enabled'] === 0 || $smtp['debug'] === 0) {
    		    echo "<META HTTP-EQUIV=\"refresh\" content=\"0;URL=".$danke."\">";
            }

    		exit;
		}
		else{
			$fehler['Sendmail'] = "<span class='errormsg' style='display: block;'>Die SMTP Verbindung konnte nicht hergestellt werden.<br /><span style='text-decoration:underline;'>Mögliche Ursachen:</span><br />- Die SMTP Daten sind nicht korrekt. <br />- Eine Verbindung zu einem externen Mailserver soll hergestellt werden. Wenden Sie sich an Ihren Hosting-Anbieter, um eine Portfreischaltung zu beantragen.</span>";
		}
	}
}

// clean post
foreach($_POST as $key => $value){
    $_POST[$key] = htmlentities($value, ENT_QUOTES, "UTF-8");
}
?>
<?php




function sendMyMail($fromMail, $fromName, $toMail, $subject, $content, $attachments=array()){

	$boundary = md5(uniqid(time()));
	$eol = PHP_EOL;

	// header
	$header = "From: =?UTF-8?B?".base64_encode(stripslashes($fromName))."?= <".$fromMail.">".$eol;
	$header .= "Reply-To: <".$fromMail.">".$eol;
	$header .= "MIME-Version: 1.0".$eol;
	if(is_array($attachments) && 0<count($attachments)){
		$header .= "Content-Type: multipart/mixed; boundary=\"".$boundary."\"";
	}
	else{
		$header .= "Content-type: text/plain; charset=utf-8";
	}


	// content with attachments
	if(is_array($attachments) && 0<count($attachments)){

		// content
		$message = "--".$boundary.$eol;
		$message .= "Content-type: text/plain; charset=utf-8".$eol;
		$message .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
		$message .= $content.$eol;

		// attachments
		foreach($attachments as $filename=>$filecontent){
			$filecontent = chunk_split(base64_encode($filecontent));
			$message .= "--".$boundary.$eol;
			$message .= "Content-Type: application/octet-stream; name=\"".$filename."\"".$eol;
			$message .= "Content-Transfer-Encoding: base64".$eol;
			$message .= "Content-Disposition: attachment; filename=\"".$filename."\"".$eol.$eol;
			$message .= $filecontent.$eol;
		}
		$message .= "--".$boundary."--";
	}
	// content without attachments
	else{
		$message = $content;
	}

	// subject
	$subject = "=?UTF-8?B?".base64_encode($subject)."?=";

	// send mail
	return mail($toMail, $subject, $message, $header);
}

?>
<!DOCTYPE html>
<html lang="de-DE">
	<head>
		<meta charset="utf-8">
		<meta name="language" content="de"/>
		<meta name="description" content="kontaktformular.com"/>
		<meta name="revisit" content="After 7 days"/>
		<meta name="robots" content="INDEX,FOLLOW"/>
		<title>kontaktformular.com</title>
		<link href="css/style-kontaktformular.css" rel="stylesheet" type="text/css" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
	</head>
	<body id="Kontaktformularseite">
			<?php 
				if(
					(isset($fehler["Honeypot"]) && $fehler["Honeypot"] != "") || 
					(isset($fehler["Zeitsperre"]) && $fehler["Zeitsperre"] != "") ||
					(isset($fehler["Klick-Check"]) && $fehler['Klick-Check'] != "") ||
					(isset($fehler["Links"]) && $fehler['Links'] != "") ||
					(isset($fehler["Badwordfilter"]) && $fehler['Badwordfilter'] != "") || 
					(isset($fehler["Sendmail"]) && $fehler['Sendmail'] != "") ||
					(isset($fehler["upload"]) && $fehler['upload'] != "") 
				){
					?>
<div class="row">
					<label style="width:100%;"><?php if (isset($fehler["Honeypot"]) && $fehler["Honeypot"] != "") { echo $fehler["Honeypot"]; } ?>
							<?php if (isset($fehler["Zeitsperre"]) && $fehler["Zeitsperre"] != "") { echo $fehler["Zeitsperre"]; } ?>
							<?php if (isset($fehler["Klick-Check"]) && $fehler["Klick-Check"] != "") { echo $fehler["Klick-Check"]; } ?>
							<?php if (isset($fehler["Links"]) && $fehler["Links"] != "") { echo $fehler["Links"]; } ?>
							<?php if (isset($fehler["Badwordfilter"]) && $fehler["Badwordfilter"] != "") { echo $fehler["Badwordfilter"]; } ?>
							<?php if (isset($fehler["Sendmail"]) && $fehler["Sendmail"] != "") { echo $fehler["Sendmail"]; } ?>
							<?php if (isset($fehler["upload"]) && $fehler["upload"] != "") { echo $fehler["upload"]; } ?></label>
					
				</div>
			<?php
		}
	
	
	?><form class="kontaktformular" action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data">
		
		
		
		
		
		
			<input type="hidden" name="action" value="smail" />
			<input type="hidden" name="content" value="formular"/>


						
				<div class="row column-2">
                    <div>
    					<label>Name: <span class="pflichtfeld">*</span></label>
    					<div class="field">
    						<?php if ($fehler["name"] != "") { echo $fehler["name"]; } ?><input type="text" name="name" maxlength="<?php echo $zeichenlaenge_name; ?>" value="<?php echo $_POST['name']; ?>"  <?php if ($fehler["name"] != "") { echo 'class="errordesignfields"'; } ?> <?php if($cfg['HTML5_FEHLERMELDUNGEN']) { ?> required <?php } ?>/>

    					</div>
                    </div>
                    <div>
    					<label>E-Mail: <span class="pflichtfeld">*</span></label>
    					<div class="field">
    						<?php if ($fehler["email"] != "") { echo $fehler["email"]; } ?><input type="text" name="email" maxlength="<?php echo $zeichenlaenge_email; ?>" value="<?php echo $_POST['email']; ?>"  <?php if ($fehler["email"] != "") { echo 'class="errordesignfields"'; } ?> <?php if($cfg['HTML5_FEHLERMELDUNGEN']) { ?> required <?php } ?>/>

    					</div>
                    </div>
				</div>


			


				 <div class="row">
					<label>Betreff: <span class="pflichtfeld">*</span></label>
					<div class="field">
						<?php if ($fehler["betreff"] != "") { echo $fehler["betreff"]; } ?><input type="text" name="betreff" style="width:100%;" maxlength="<?php echo $zeichenlaenge_betreff; ?>" value="<?php echo $_POST['betreff']; ?>"  <?php if ($fehler["betreff"] != "") { echo 'class="errordesignfields"'; } ?> <?php if($cfg['HTML5_FEHLERMELDUNGEN']) { ?> required <?php } ?>/>

					</div>
				</div>


				<div class="row">
					<label>Nachricht: <span class="pflichtfeld">*</span></label>
					<div class="field">
						<?php if ($fehler["nachricht"] != "") { echo $fehler["nachricht"]; } ?><textarea name="nachricht"  cols="30" rows="8" <?php if ($fehler["nachricht"] != "") { echo 'class="errordesignfields"'; } ?> <?php if($cfg['HTML5_FEHLERMELDUNGEN']) { ?> required <?php } ?>><?php echo $_POST['nachricht']; ?></textarea>

					</div>
				</div>


			<?php
			if(0<$cfg['NUM_ATTACHMENT_FIELDS']){
				echo '<div class="row">';
					echo '<label>Dateianhang: </label>';
				echo '<div>';
				  for ($i=0; $i < $cfg['NUM_ATTACHMENT_FIELDS']; $i++) {
					  echo '<div><div class="field"><input type="file" style="margin-top:10px;" size="12" name="f[]" /></div></div></div></div>';
				  }

			}
			?>






			 



<?php
// -------------------- SPAMPROTECTION START ----------------------

if($cfg['Honeypot']){ ?>
	<div style="height: 2px; overflow: hidden;">
		<label style="margin-top: 10px;">Das nachfolgende Feld muss leer bleiben, damit die Nachricht gesendet wird!</label>
		<div style="margin-top: 10px;"><input type="email" name="mail" value="" /></div>
	</div>
<?php }

if($cfg['Zeitsperre']){ ?>
	<input type="hidden" name="chkspmtm" value="<?php echo time(); ?>" />
<?php }

if($cfg['Klick-Check']){ ?>
	<input type="hidden" name="chkspmkc" value="chkspmbt" />
<?php }


if($cfg['Sicherheitscode']) { ?>
   <div class="row" style="margin-top:40px;">
					<label>Sicherheitscode:</label><br />
					<div class="field"><img src="captcha/captcha.php" alt="Sicherheitscode" title="kontaktformular.com-sicherheitscode" id="captcha" />
						<a href="javascript:void(0);" onclick="javascript:document.getElementById('captcha').src='captcha/captcha.php?'+Math.random();cursor:pointer;">
							<span class="captchareload"><i style="color:grey;" class="fas fa-sync-alt"></i></span>
						</a>
					</div>
				</div>
 <div class="row" style="margin-top:10px;">
					<label>Bitte eingeben: <span class="pflichtfeld">*</span></label>

					
					<div class="field">
						<?php if ($fehler["captcha"] != "") { echo $fehler["captcha"]; } ?><input type="text" name="sicherheitscode" maxlength="150" value=""  <?php if ($fehler["captcha"] != "") { echo 'class="errordesignfields"'; } ?>/>

					</div>
				</div>
				
				
				
	<div class="row" style="margin-top:10px;">
<label></label>
<div class="field">

				<?php
if ($fehler) {
}
   else {
      print "Diese Eingabe dient zum Schutz vor Spam.";
         }
?>

</div>
</div>
  

<?php }

if($cfg['Sicherheitsfrage']) { ?>
  <div class="row" style="margin-top:40px;">
					<label><?php echo $q[1]; ?>  <input type="hidden" name="q_id" value="<?php echo $q[0]; ?>"/></label>
					<div class="field">
					</div>
				</div>
 <div class="row" style="margin-top:80px;">
					<label>Bitte eingeben: <span class="pflichtfeld">*</span></label>

					
					<div class="field">
							<input type="text" <?php if ($fehler["q_id12"] != "") { echo 'class="errordesignfields"'; } ?> name="q"/>
<?php if ($fehler["q_id12"] != "") { echo $fehler["q_id12"]; } ?>

				<?php if ($fehler) { }   else {   print "";     } ?>

					</div>
				</div>
				
				
				
	<div class="row" style="margin-top:10px;">
<label></label>
<div class="field">

				<?php
if ($fehler) {
}
   else {
      print "Diese Eingabe dient zum Schutz vor Spam.";
         }
?>

</div>
</div>

  
  

<?php } 

// -------------------- SPAMPROTECTION ENDE ----------------------
		{ ?>






	<?php }
		
		// -------------------- MAIL-COPY START ----------------------

		if(1==$cfg['Kopie_senden']) { ?>
		<div class="row">
   <div class="checkbox-inline">
<input type=checkbox id="inlineCheckbox11" name="mail-copy" value="1" <?php if (isset($_POST['mail-copy']) && $_POST['mail-copy']=='1') echo(' checked="checked" '); ?> /> <span>Kopie der Nachricht per E-Mail senden</span></div></div>
			
	
	
<?php } 

		// -------------------- MAIL-COPY ENDE ----------------------
		
		
		// -------------------- DATAPROTECTION START ----------------------

if($cfg['DATENSCHUTZ_ERKLAERUNG']) { ?>


<div class="row">
   <div class="checkbox-inline">
<?php if ($fehler["datenschutz"] != "") { echo $fehler["datenschutz"]; } ?><input type=checkbox id="inlineCheckbox11" name="datenschutz" value="akzeptiert"<?php if ($_POST['datenschutz']=='akzeptiert') echo(' checked="checked" '); ?> <?php if($cfg['HTML5_FEHLERMELDUNGEN']) { ?> required <?php } ?> /> <a href="<?php echo "$datenschutzerklaerung"; ?>" target="_blank">Ich stimme der Datenschutzerklärung zu.</a> *</div></div>







<?php } 

// -------------------- DATAPROTECTION ENDE ----------------------
 ?>
 
 






<div class="pflichtfeldhinweis"><br /><b>Hinweis:</b> Felder mit <span class="pflichtfeld">*</span> m&uuml;ssen ausgef&uuml;llt werden.</div>
			   <div class="buttons"><br /><input type="submit" name="kf-km" value="Senden" style="width:100%;font-size:14px;" onclick="tescht();"/>
			  </div>


<div class="copyright"><!-- Dieser Copyrighthinweis darf NICHT entfernt werden. --><br /><br /><a href="https://www.kontaktformular.com" title="kontaktformular.com">&copy; by kontaktformular.com - Alle Rechte vorbehalten.</a></div>
	
	<?php if($cfg['Klick-Check']){ ?>
	<script type="text/javascript">
		function chkspmkcfnk(){
			document.getElementsByName('chkspmkc')[0].value = 'chkspmhm';
		}
		document.getElementsByName('kf-km')[0].addEventListener('mouseenter', chkspmkcfnk);
		document.getElementsByName('kf-km')[0].addEventListener('touchstart', chkspmkcfnk);
	</script>
<?php } ?>
	
		</form>
	</body>
</html>

