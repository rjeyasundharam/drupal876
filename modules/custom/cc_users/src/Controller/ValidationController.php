<?php

namespace Drupal\cc_users\Controller;

use Drupal\Core\Form\FormStateInterface;

class ValidationController{

	public function validatefields(array &$form, FormStateInterface $form_state) {
		$value=$form_state->getValues();
	  if(isset($value['name'])){
			$name = $value['name'];
			if (!preg_match("/^[a-zA-Z0-9 ]*$/",$name)) {
			  $message= t("Only letters , Numbers and white space allowed First name"); 
				$form['account']['name']['citem_error']=$message;
			}
		}
		else{
		  $message= t("Please enter your first name"); 
			$form['account']['name']['citem_error']=$message;
		}

		if(isset($value['field_last_name'][0]['value'])){
			$name = $value['field_last_name'][0]['value'];
			if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
			  $message= t("Only letters and white space allowed last name $name"); 
				// $form_state->setError(['field_last_name']['widget'][0]['value'], $message);
				$form['field_last_name']['widget'][0]['value']['citem_error']=$message;
			}
		}
		else{
		  $message= t('Please enter your last name'); 
			$form_state->setError(['field_last_name']['widget'][0]['value'], $message);
			$form['field_last_name']['widget'][0]['value']['citem_error']=$message;
		}
	}
  
  public function validate($value){
  	$error=[];
		if(isset($value['name'])){
			$name = $value['name'];
			if (!preg_match("/^[a-zA-Z0-9 ]*$/",$name)) {
			  $error['name'] = t("Only letters , Numbers and white space allowed First name"); 
			}
		}
		else{
			$error['name'] = t("Please enter your first name"); 
		}

		if(isset($value['field_last_name'][0]['value'])){
			$name = $value['field_last_name'][0]['value'];
			if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
			  $error['field_last_name[0][value'] = t("Only letters and white space allowed last name $name"); 
			}
		}
		else{
			$error['field_last_name[0][value'] = t("Please enter your last name"); 
		}

		if(isset($value['field_usertype'])){
			$usertype = $value['field_usertype'];
			if (!preg_match("/^[a-zA-Z ]*$/",$usertype)) {
			  $error['field_usertype'] = "Please select an option that best applies to you"; 
			}
		}
		else{
			$error['field_usertype'] = "Please select an option that best applies to you"; 
		}

		if(isset($value['field_certify']['value'])){
			$certify = $value['field_certify']['value'];
			if ($certify==0) {
			  $error['field_certify'] = "Certify Field is required"; 
			}
		}
		else{
			$error['field_certify'] = "Certify Field is required"; 
		}
		
		if(isset($value['mail'])){
			$email = $value['mail'];
			if (!ValidationController::validEmail($email)) {
			  $error['mail'] = "Please enter your email"; 
			}
		}
		else{
			$error['mail'] = "Please enter your email"; 
		}

		return $error;
  }

  public function validEmail($email){
    // First, we check that there's one @ symbol, and that the lengths are right
    if (!preg_match("/^[^@]{1,64}@[^@]{1,255}$/", $email)) {
        // Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
        return false;
    }
    // Split it into sections to make life easier
    $email_array = explode("@", $email);
    $local_array = explode(".", $email_array[0]);
    for ($i = 0; $i < sizeof($local_array); $i++) {
        if (!preg_match("/^(([A-Za-z0-9!#$%&'*+\/=?^_`{|}~-][A-Za-z0-9!#$%&'*+\/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$/", $local_array[$i])) {
            return false;
        }
    }
    if (!preg_match("/^\[?[0-9\.]+\]?$/", $email_array[1])) { // Check if domain is IP. If not, it should be valid domain name
        $domain_array = explode(".", $email_array[1]);
        if (sizeof($domain_array) < 2) {
            return false; // Not enough parts to domain
        }
        for ($i = 0; $i < sizeof($domain_array); $i++) {
            if (!preg_match("/^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$/", $domain_array[$i])) {
                return false;
            }
        }
    }

    return true;
	}

}