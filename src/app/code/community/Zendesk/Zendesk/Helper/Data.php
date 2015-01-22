<?php
/**
 * Copyright 2012 Zendesk.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class Zendesk_Zendesk_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getUrl($object = '', $id = null, $format = 'old')
    {
        $protocol = 'https://';
        $domain = Mage::getStoreConfig('zendesk/general/domain');
        $root = ($format === 'old') ? '' : '/agent/#';

        $base = $protocol . $domain . $root;

        switch($object) {
            case '':
                return $base;
                break;

            case 'ticket':
                return $base . '/tickets/' . $id;
                break;

            case 'user':
                return $base . '/users/' . $id;
                break;

            case 'raw':
                return $protocol . $domain . '/' . $id;
                break;
        }
    }

    /**
     * Returns configured Zendesk Domain
     * format: company.zendesk.com
     *
     * @return mixed Zendesk Account Domain
     */
    public function getZendeskDomain()
    {
        return Mage::getStoreConfig('zendesk/general/domain');
    }
    
    
    /**
     * Returns if SSO is enabled for EndUsers
     * @return integer
     */
    public function isSSOEndUsersEnabled()
    {
        return Mage::getStoreConfig('zendesk/sso_frontend/enabled');
    }

    /**
     * Returns if SSO is enabled for Admin/Agent Users
     * @return integer
     */
    public function isSSOAdminUsersEnabled()
    {
        return Mage::getStoreConfig('zendesk/sso/enabled');
    }

    /**
     * Returns frontend URL where authentication process starts for EndUsers
     *
     * @return string SSO Url to auth EndUsers
     */
    public function getSSOAuthUrlEndUsers()
    {
        return Mage::getUrl('zendesk/sso/login');
    }

    /**
     * Returns backend URL where authentication process starts for Admin/Agents
     *
     * @return string SSO Url to auth Admin/Agents
     */
    public function getSSOAuthUrlAdminUsers()
    {
        return Mage::helper('adminhtml')->getUrl('*/zendesk/login');
    }

    /**
     * Returns Zendesk Account Login URL for normal access
     * format: https://<zendesk_account>/<route>
     *
     * @return string Zendesk Account login url
     */
    public function getZendeskAuthNormalUrl()
    {
        $protocol = 'https://';
        $domain = $this->getZendeskDomain();
        $route = '/access/normal';

        return $protocol . $domain . $route;
    }

    /**
     * Returns Zendesk Login Form unauthenticated URL
     * format: https://<zendesk_account>/<route>
     *
     * @return string Zendesk Account login unauthenticated form url
     */
    public function getZendeskUnauthUrl()
    {
        $protocol = 'https://';
        $domain = $this->getZendeskDomain();
        $route = '/access/unauthenticated';

        return $protocol . $domain . $route;
    }
    
    public function getApiToken($generate = true)
    {
        // Grab any existing token from the admin scope
        $token = Mage::getStoreConfig('zendesk/api/token', 0);

        if( (!$token || strlen(trim($token)) == 0) && $generate) {
            $token = $this->setApiToken();
        }

        return $token;
    }

    public function setApiToken($token = null)
    {
        if(!$token) {
            $token = md5(time());
        }
        Mage::getModel('core/config')->saveConfig('zendesk/api/token', $token, 'default');

        return $token;
    }

    /**
     * Returns the provisioning endpoint for new setups.
     *
     * This uses the config/zendesk/provision_url XML path to retrieve the setting, with a default value set in
     * the extension config.xml file. This can be overridden in your website's local.xml file.
     * @return null|string URL or null on failure
     */
    public function getProvisionUrl()
    {
        $config = Mage::getConfig();
        $data = $config->getNode('zendesk/provision_url');
        if(!$data) {
            return null;
        }
        return (string)$data;
    }

    public function getProvisionToken($generate = false)
    {
        $token = Mage::getStoreConfig('zendesk/hidden/provision_token', 0);

        if( (!$token || strlen(trim($token)) == 0) && $generate) {
            $token = $this->setProvisionToken();
        }

        return $token;
    }

    public function setProvisionToken($token = null)
    {
        if(!$token) {
            $token = md5(time());
        }

        Mage::getModel('core/config')->saveConfig('zendesk/hidden/provision_token', $token, 'default');
        Mage::getConfig()->removeCache();

        return $token;
    }

    public function getOrderDetail($order)
    {
        // if the admin site has a custom URL, use it
        $urlModel = Mage::getModel('adminhtml/url')->setStore('admin');

        $orderInfo = array(
            'id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'created' => $order->getCreatedAt(),
            'updated' => $order->getUpdatedAt(),
            'customer' => array(
                'name' => $order->getCustomerName(),
                'email' => $order->getCustomerEmail(),
                'ip' => $order->getRemoteIp(),
                'guest' => (bool)$order->getCustomerIsGuest(),
            ),
            'store' => $order->getStoreName(),
            'total' => $order->getGrandTotal(),
            'currency' => $order->getOrderCurrencyCode(),
            'items' => array(),
            'admin_url' => $urlModel->getUrl('adminhtml/sales_order/view', array('order_id' => $order->getId())),
        );

        foreach($order->getItemsCollection(array(), true) as $item) {
            $orderInfo['items'][] = array(
                'sku' => $item->getSku(),
                'name' => $item->getName(),
            );
        }

        return $orderInfo;
    }

    public function getSupportEmail($store = null)
    {
        $domain = Mage::getStoreConfig('zendesk/general/domain', $store);
        $email = 'support@' . $domain;

        return $email;
    }

    public function loadCustomer($email, $website = null)
    {
        $customer = null;

        if(Mage::getModel('customer/customer')->getSharingConfig()->isWebsiteScope()) {
            // Customer email address can be used in multiple websites so we need to
            // explicitly scope it
            if($website) {
                // We've been given a specific website, so try that
                $customer = Mage::getModel('customer/customer')
                    ->setWebsiteId($website)
                    ->loadByEmail($email);
            } else {
                // No particular website, so load all customers with the given email and then return a single object
                $customers = Mage::getModel('customer/customer')
                    ->getCollection()
                    ->addFieldToFilter('email', array('eq' => array($email)));
                if($customers->getSize()) {
                    $id = $customers->getLastItem()->getId();
                    $customer = Mage::getModel('customer/customer')->load($id);
                }
            }

        } else {
            // Customer email is global, so no scoping issues
            $customer = Mage::getModel('customer/customer')->loadByEmail($email);
        }

        return $customer;
    }

    /**
     * Retrieve Use External ID config option
     *
     * @return integer
     */
    public function isExternalIdEnabled()
    {
        return Mage::getStoreConfig('zendesk/general/use_external_id');
    }
    
    /**
     * Retrieve ticket statistics
     */
    public function getTicketTotals($type = null, $from = null, $to = null)
    {
        $tickets = Mage::getModel('zendesk/api_tickets')->all();
        
        if( is_null($tickets) )
        {
            return false;
        }
        
        $totals = array(
            'open'      =>  0,
            'new'       =>  0,
            'solved'    =>  0,
            'closed'    =>  0
        );

        if( $from )
            $from = strtotime($from);
        else
            $from = 0;

        foreach( $tickets as $ticket )
        {
            if( $from || $to )
            {
                if( strtotime($ticket['created_at']) > $from)
                {
                    if( $to )
                    {
                        if( strtotime($ticket['created_at']) < strtotime($to) )
                        {
                            if( isset($totals[$ticket['status']]) )
                                $totals[$ticket['status']]++;
                        }
                    }
                    else
                    {
                        if( isset($totals[$ticket['status']]) )
                            $totals[$ticket['status']]++;
                    }
                }
            }
            else
            {
                if( isset($totals[$ticket['status']]) )
                    $totals[$ticket['status']]++;
            }
        }
        
        if( $type && isset($totals[$type]))
        {
            return $totals[$type];
        }
        else
        {
            return $totals;
        }
    }
    
    public function getExcerpt($row)
    {
        if( !$row )
        {
            return Mage::helper('zendesk')->__('Subject');
        }
        $url = Mage::helper('zendesk')->getUrl('ticket', $row['id']);
        if( $row['subject'] )
        {
            return '<a href="' . $url . '" target="_blank">' . $row['subject']. '</a>'; 
        }
        else
        {
            $subject = "";
            
            $text = explode("Comment:",$row['description']); 
            $text = explode("------------------", $text[count($text)-1]);
            $text = $text[0];
           
            if( strlen($text) > 30 )
            {
                for( $index = 0; $index <= 30; $index++ )
                {
                    if( $index === 30)
                    {
                        while( $text[$index] !== " " && $index <= strlen($text))
                        {
                            $subject .= $text[$index];
                            $index++;
                        }
                        break;
                    }
                    $subject .= $text[$index];
                }
                $subject .= "...";
            }
            else
            {
                $subject = $text;
            }
            
            return '<a href="' . $url . '" target="_blank">' . $subject . '</a>'; 
        }
        
    }
        
    public function getAdminSettings() {
        $admin = Mage::getSingleton('admin/session')->getUser();
        if( $admin )
        {
            $adminId    = $admin->getUserId();
            $settings   = Mage::getModel('zendesk/settings')->loadByAdminId($adminId);
            return $settings;
        }
        else
        {
            return false;
        }
        
    }
    
    public function getStatusMap()
    {
        return array(
            'new'       =>  'New',
            'open'      =>  'Open',
            'pending'   =>  'Pending',
            'solved'    =>  'Solved',
            'closed'    =>  'Closed'
        );
    }
        
    public function getPriorityMap()
    {
        return array(
            'low'       =>  'Low',
            'normal'    =>  'Normal',
            'high'      =>  'High',
            'urgent'    =>  'Urgent'
        );
    }
}
