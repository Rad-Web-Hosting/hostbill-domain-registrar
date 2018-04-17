<?php

/* * ********************************************************************
 * Customization Services by ModulesGarden.com
 * Copyright (c) ModulesGarden, INBS Group Brand, All Rights Reserved 
 * (2014-02-14, 11:50:55)
 * 
 *
 *  CREATED BY MODULESGARDEN       ->        http://modulesgarden.com
 *  CONTACT                        ->       contact@modulesgarden.com
 *
 *
 *
 *
 * This software is furnished under a license and may be used and copied
 * only  in  accordance  with  the  terms  of such  license and with the
 * inclusion of the above copyright notice.  This software  or any other
 * copies thereof may not be provided or otherwise made available to any
 * other person.  No title to and  ownership of the  software is  hereby
 * transferred.
 *
 *
 * ******************************************************************** */
use domainsReseller\module\PdoWrapper;

include_once dirname(__FILE__) . DS . 'PdoWrapper.php';

/**
 * @author Grzegorz Draganik <grzegorz@modulesgarden.com>
 */

class radwebhosting extends DomainModule {
	
	protected $description	= 'radwebhosting';
	protected $modname		= "radwebhosting";
	protected $version		= "1.0";
	
	protected $client_data	= array();
	protected $configuration= array(
		'user_email' => array(
			'value'		=> '',
			'type'		=> 'input',
			'default'	=> false
		),
		'api_key' => array(
			'value'		=> '',
			'type'		=> 'input',
			'default'	=> false
		),
	);
	protected $lang = array(
		'english' => array(
			'user_email'=> 'User Email',
			'api_key'	=> 'API Key',
		)
	);
	protected $commands = array('Register','Transfer','Renew','getNameServers','updateNameServers','getEppCode','RequestDelete');


	
	
	public function synchInfo(){
		$response = $this->_callApi(array(
			"action"			=> "Sync",
			"token"				=> $this->configuration['api_key']['value'],
			"authemail"			=> $this->configuration['user_email']['value'],
			"sld"				=> $this->options['sld'],
			"tld"				=> $this->options['tld'],
		));

		if (isset($response['result'])){
			NativeMySQL::connect();
                        $domainid = $this->getDomainId();
                        
			if ($response['result'] == 'success'){
				
				if ($response['expirydate']){
					if (PdoWrapper::query('UPDATE hb_domains SET expires = "'.$response['expirydate'].'" WHERE id=' . $domainid))
						$this->addInfo('Expiry date has been updated');
				}
				if ($response['active'] == true){
                                        PdoWrapper::query('UPDATE hb_domains SET status = "Active" WHERE id=' . $domainid);
                                        if($this->status == "Pending" || $this->status == "Pending Transfer" || $this->status == "Pending Registration") {
                                            PdoWrapper::query('UPDATE hb_domains SET date_created = "'.date('Y-m-d').'" WHERE id=' . $domainid);
                                        }
					$this->addInfo('Domain is active');
					return true;
				} else {
					if (strtotime(date( "Ymd" )) <= strtotime( $response['expirydate'] )) {
                                                PdoWrapper::query('UPDATE hb_domains SET status = "Active"  WHERE id=' . $domainid);
						$this->addInfo('Domain is active');
						return true;
					} else {
						PdoWrapper::query('UPDATE hb_domains SET status = "Expired"  WHERE id=' . $domainid);
						$this->addError('Domain is expired');
						return false;
					}
				}
			}
			
			$this->addError($response['msg']);
			return false;
		}
		
		$this->addError('Connection error');
	}
	
	public function testConnection(){
		$response = $this->_callApi(array(
			"action"	=> "Version",
			"token"     => $this->configuration['api_key']['value'],
			"authemail" => $this->configuration['user_email']['value']
		));
		
		return isset($response['result']) && $response['result'] == 'success';
	}
	
	public function Register(){
		 $data = array(
			"action"                => "RegisterDomain",
			"token"                 => $this->configuration['api_key']['value'],
			"authemail"             => $this->configuration['user_email']['value'],
			"sld"                   => $this->options['sld'],
			"tld"                   => $this->options['tld'],
			"regperiod"             => $this->period,
			"nameserver1"		=> $this->options['ns1'] ? $this->options['ns1'] : $this->details['ns1'],
			"nameserver2"		=> $this->options['ns2'] ? $this->options['ns2'] : $this->details['ns2'],
			"nameserver3"		=> $this->options['ns3'] ? $this->options['ns3'] : $this->details['ns3'],
			"nameserver4"		=> $this->options['ns4'] ? $this->options['ns4'] : $this->details['ns4'],
			"nameserver5"		=> $this->options['ns5'] ? $this->options['ns5'] : $this->details['ns5'],
			"dnsmanagement"		=> 0,
			"emailforwarding"	=> 0,
			"idprotection"		=> 0,
			"firstname"             => $this->client_data['firstname'],
			"lastname"              => $this->client_data['lastname'],
			"companyname"		=> $this->client_data['companyname'],
			"address1"              => $this->client_data['address1'],
			"address2"              => $this->client_data['address2'],
			"city"                  => $this->client_data['city'],
			"state"                 => $this->client_data['state'],
			"country"               => $this->client_data['country'],
			"postcode"              => $this->client_data['postcode'],
			"phonenumber"           => $this->client_data['phonenumber'],
			"fullphonenumber"       => $this->client_data['phonenumber'],
			"email"                 => $this->client_data['email'],
			"adminfirstname"	=> $this->domain_contacts['admin']['firstname'],
			"adminlastname"		=> $this->domain_contacts['admin']['lastname'],
			"admincompanyname"	=> $this->domain_contacts['admin']['companyname'],
			"adminaddress1"		=> $this->domain_contacts['admin']['address1'],
			"adminaddress2"		=> $this->domain_contacts['admin']['address2'],
			"admincity"			=> $this->domain_contacts['admin']['city'],
			"adminstate"		=> $this->domain_contacts['admin']['state'],
			"admincountry"		=> $this->domain_contacts['admin']['country'],
			"adminpostcode"		=> $this->domain_contacts['admin']['postcode'],
			"adminphonenumber"	=> $this->domain_contacts['admin']['phonenumber'],
			"adminfullphonenumber" => $this->domain_contacts['admin']['phonenumber'],
			"adminemail"		=> $this->domain_contacts['admin']['email'],
//			"domainfields"		=> base64_encode(serialize(array_values(array(
				// un supported
//			))))
		);
                $response = $this->_callApi($data);
		
		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$this->addInfo('Domain has been registered');
				return true;
			}
                        
                        if($response['msg'] == '----536b775905076----')
                        {
                            $response = $this->_callApi($data);
                        }
			
                        if ($response['result'] == 'success'){
				$this->addInfo('Domain has been registered');
				return true;
			}
                        
			$this->addError($response['msg']);
			return false;
		}
		
		$this->addError('Connection error while registering');
	}
	
	public function Transfer(){
		$response = $this->_callApi(array(
			"action"			=> 'TransferDomain',
			"token"				=> $this->configuration['api_key']['value'],
			"authemail"			=> $this->configuration['user_email']['value'],
			"sld"				=> $this->options['sld'],
			"tld"				=> $this->options['tld'],
			'transfersecret'	=> $this->details['epp_code'],
			"regperiod"			=> $this->period,
			"nameserver1"		=> $this->options['ns1'] ? $this->options['ns1'] : $this->details['ns1'],
			"nameserver2"		=> $this->options['ns2'] ? $this->options['ns2'] : $this->details['ns2'],
			"nameserver3"		=> $this->options['ns3'] ? $this->options['ns3'] : $this->details['ns3'],
			"nameserver4"		=> $this->options['ns4'] ? $this->options['ns4'] : $this->details['ns4'],
			"nameserver5"		=> $this->options['ns5'] ? $this->options['ns5'] : $this->details['ns5'],
			'dnsmanagement'		=> 0,
			'emailforwarding'	=> 0,
			'idprotection'		=> 0,
			"firstname"			=> $this->client_data['firstname'],
			"lastname"			=> $this->client_data['lastname'],
			"companyname"		=> $this->client_data['companyname'],
			"address1"			=> $this->client_data['address1'],
			"address2"			=> $this->client_data['address2'],
			"city"				=> $this->client_data['city'],
			"state"				=> $this->client_data['state'],
			"country"			=> $this->client_data['country'],
			"postcode"			=> $this->client_data['postcode'],
			"phonenumber"		=> $this->client_data['phonenumber'],
			"email"				=> $this->client_data['email'],
			'fullphonenumber'   => $this->client_data['phonenumber'],
			
			"adminfirstname"	=> $this->domain_contacts['admin']['firstname'],
			"adminlastname"		=> $this->domain_contacts['admin']['lastname'],
			"admincompanyname"	=> $this->domain_contacts['admin']['companyname'],
			"adminaddress1"		=> $this->domain_contacts['admin']['address1'],
			"adminaddress2"		=> $this->domain_contacts['admin']['address2'],
			"admincity"			=> $this->domain_contacts['admin']['city'],
			"adminstate"		=> $this->domain_contacts['admin']['state'],
			"admincountry"		=> $this->domain_contacts['admin']['country'],
			"adminpostcode"		=> $this->domain_contacts['admin']['postcode'],
			"adminphonenumber"	=> $this->domain_contacts['admin']['phonenumber'],
			"adminfullphonenumber" => $this->domain_contacts['admin']['phonenumber'],
			"adminemail"		=> $this->domain_contacts['admin']['email'],
//			"domainfields"		=> base64_encode(serialize(array_values(array(
				// un supported
//			)))),
		));
		
		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$this->addInfo('Domain has been transfered');
				return true;
			}
			
			$this->addError($response['msg']);
			return false;
		}
		
		$this->addError('Connection error while transfering');
	}
	
	public function Renew(){
		$response = $this->_callApi(array(
			"action"		=> 'RenewDomain',
			"token"			=> $this->configuration['api_key']['value'],
			"authemail"		=> $this->configuration['user_email']['value'],
			"sld"			=> $this->options['sld'],
			"tld"			=> $this->options['tld'],
			'regperiod'		=> $this->period
		));
		
		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$this->addInfo('Domain has been Renewed');
				return true;
			}
			
			$this->addError($response['msg']);
			return false;
		}
		
		$this->addError('Connection error while renewing');
	}
	
	public function getNameServers(){
		$response = $this->_callApi(array(
			"action"		=> 'GetNameservers',
			"token"			=> $this->configuration['api_key']['value'],
			"authemail"		=> $this->configuration['user_email']['value'],
			"sld"			=> $this->options['sld'],
			"tld"			=> $this->options['tld'],
		));
		
		if (isset($response['result'])){
			$details = array();
			if ($response['result'] == 'success'){
				for($i = 1; $i <= 5; $i++){
					$this->details['ns'.$i] = $response['ns'.$i];
					$details[] = $response['ns'.$i];
				}
				NativeMySQL::connect();
				PdoWrapper::query('UPDATE hb_domains SET nameservers = "' . implode('|', $details).'"');
				
				$this->addInfo('Nameservers has beed updated in Hostbill');
				return $details;
			}
			
			$this->addError($response['msg']);
			return false;
		}
		
		$this->addError('Connection error while getting nameservers');
	}
	
	public function updateNameServers(){
		$response = $this->_callApi(array(
			"action"		=> 'SaveNameservers',
			"token"			=> $this->configuration['api_key']['value'],
			"authemail"		=> $this->configuration['user_email']['value'],
			"sld"			=> $this->options['sld'],
			"tld"			=> $this->options['tld'],
			"nameserver1"	=> $this->options['ns1'] ? $this->options['ns1'] : $this->details['ns1'],
			"nameserver2"	=> $this->options['ns2'] ? $this->options['ns2'] : $this->details['ns2'],
			"nameserver3"	=> $this->options['ns3'] ? $this->options['ns3'] : $this->details['ns3'],
			"nameserver4"	=> $this->options['ns4'] ? $this->options['ns4'] : $this->details['ns4'],
			"nameserver5"	=> $this->options['ns5'] ? $this->options['ns5'] : $this->details['ns5'],
		));
		
		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$this->addInfo('Nameservers has been updated in the registrar');
				return true;
			}
			
			$this->addError($response['msg']);
			return false;
		}
		
		$this->addError('Connection error');
	}
	
	public function getEppCode(){
		$response = $this->_callApi(array(
			"action"		=> 'GetEPPCode',
			"token"			=> $this->configuration['api_key']['value'],
			"authemail"		=> $this->configuration['user_email']['value'],
			"sld"			=> $this->options['sld'],
			"tld"			=> $this->options['tld'],
		));
		
		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$this->details['epp_code'] = $response['eppcode'];
				$this->addInfo('Epp Code: ' . $response['eppcode']);
				return true;
			}
			
			$this->addError($response['msg']);
			return false;
		}
		
		$this->addError('Connection error');
	}
	
	public function RequestDelete(){
		$response = $this->_callApi(array(
			"action"		=> 'RequestDelete',
			"token"			=> $this->configuration['api_key']['value'],
			"authemail"		=> $this->configuration['user_email']['value'],
			"sld"			=> $this->options['sld'],
			"tld"			=> $this->options['tld'],
//			'regperiod'     => $this->period,
//            'regtype'       => $params['regtype']
		));
		
		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$this->addInfo('Request has been placed');
				return true;
			}
			
			$this->addError($response['msg']);
			return false;
		}
		
		$this->addError('Connection error');
	}
	
        public function getContactInfo() {
		
		$response = $this->_callApi(array(
			"action"		=> 'GetContactDetails',
			"token"			=> $this->configuration['api_key']['value'],
			"authemail"		=> $this->configuration['user_email']['value'],
			"sld"			=> $this->options['sld'],
			"tld"			=> $this->options['tld'],
		));
                
		if (isset($response['result'])){
			if ($response['result'] == 'success'){
				$contact = array();
                                foreach($response['Registrant'] as $key => $val) {
                                    $contact[$key] = $val;
                                }
				return $contact;
			}
			
			$this->addError($response['msg']);
			return false;
		}
		
		$this->addError('Connection error');
	}
         
	public function updateContactInfo() {
            $new = array();
            
            if(!isset($this->options['Address_1'])) {
                $this->options['First_Name']        = $this->options['firstname'];
                $this->options['Last_Name']         = $this->options['lastname'];
                $this->options['Organisation_Name'] = $this->options['companyname'];
                $this->options['Address_1']         = $this->options['address1'];
                $this->options["Address_2"]         = $this->options["address2"];
                $this->options["ZIP_Code"]          = $this->options["postcode"];
                $this->options["City"]              = $this->options["city"];
                $this->options["Country_Code"]      = $this->options["country"];
                $this->options["Phone"]             = $this->options["phonenumber"];
                $this->options["Email_Address"]     = $this->options["email"];
                $this->options["State_or_Region"]   = $this->options["state"];
            }
            
            foreach($this->options as $key => $val) {
                
                if(!is_array($val)) {
                    $new['Registrant'][$key] = $val;
                }
            }
            
            $response = $this->_callApi(array(
                    'action'            => 'SaveContactDetails',
                    "token"             => $this->configuration['api_key']['value'],
                    "authemail"		=> $this->configuration['user_email']['value'],
                    "sld"               => $this->options['sld'],
                    "tld"               => $this->options['tld'],
                    'contactdetails'    => $new,
            ));

            if (isset($response['result'])){
                if($response['result']=='success'){
                    return true;
                }
                $this->addError($response['msg']);
                return false;
            }
            
            $this->addError('Connection error');
	}
        
	public function registerNameServer() {
            $response = $this->_callApi(array(
                'action'            => 'RegisterNameserver',
                "token"             => $this->configuration['api_key']['value'],
                "authemail"         => $this->configuration['user_email']['value'],
                'sld'               => $this->options["sld"],
                'tld'               => $this->options["tld"],
                'nameserver'        => $this->options['NameServer'] . '.' . $this->options['sld'] . '.' . $this->options['tld'],
                'ipaddress'         => $this->options['NameServerIP'],
            ));
		
            if (isset($response['result'])){
                if($response['result']=='success'){
                    return true;
                }
                $this->addError($response['msg']);
                return false;
            }
            
            $this->addError('Connection error');
	}
        
	public function modifyNameServer() {
            $response = $this->_callApi(array(
                'action'            => 'ModifyNameserver',
                "token"             => $this->configuration['api_key']['value'],
                "authemail"         => $this->configuration['user_email']['value'],
                'sld'               => $this->options["sld"],
                'tld'               => $this->options["tld"],
                'nameserver'        => $this->options['NameServer'] . '.' . $this->options['sld'] . '.' . $this->options['tld'],
                'currentipaddress'  => $this->options['NameServerOldIP'],
                'newipaddress'      => $this->options['NameServerNewIP'],
            ));
		
            if (isset($response['result'])){
                if($response['result']=='success'){
                    return true;
                }
                $this->addError($response['msg']);
                return false;
            }
            
            $this->addError('Connection error');
	}
        
	public function deleteNameServer() {
            $response = $this->_callApi(array(
                'action'            => 'DeleteNameserver',
                "token"             => $this->configuration['api_key']['value'],
                "authemail"         => $this->configuration['user_email']['value'],
                'sld'               => $this->options["sld"],
                'tld'               => $this->options["tld"],
                'nameserver'        => $this->options['NameServer'] . '.' . $this->options['sld'] . '.' . $this->options['tld'],
            ));
		
            if (isset($response['result'])){
                if($response['result']=='success'){
                    return true;
                }
                $this->addError($response['msg']);
                return false;
            }
            
            $this->addError('Connection error');
	}
	
	protected function _callApi($data){
		
		$url = 'https://radwebhosting.com/client_area/domainsResellerAPI/api.php';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
        if(version_compare(phpversion(), '5.5.19', '<=')) {
            curl_setopt($this->_ch, CURLOPT_SSLVERSION, 4);
        }
                
		$result = curl_exec($ch);
		$res    = json_decode($result, true);
		curl_close($ch);
		
		return $res;
	}
}
		
	