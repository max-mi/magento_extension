<?php

/**
 * Copyright 2015 Zendesk.
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

class Zendesk_Zendesk_Model_Api2_Customer_Rest_Admin_V1 extends Zendesk_Zendesk_Model_Api2_Customer { 
    
   /**
     * Retrieve list of coupon codes.
     *
     * @return array
     */
    protected function _retrieveCollection()
    {
        $email = $this->getRequest()->getParam('email');

        // Get a list of all orders for the given email address
        // This is used to determine if a missing customer is a guest or if they really aren't a customer at all
        $orderCollection = Mage::getModel('sales/order')->getCollection()
            ->addFieldToFilter('customer_email', array('eq' => array($email)));
        $orders = array();
        if($orderCollection->getSize()) {
            foreach($orderCollection as $order) {
                $orders[] = Mage::helper('zendesk')->getOrderDetail($order);
            }
        }

        // Try to load a corresponding customer object for the provided email address
        $customer = Mage::helper('zendesk')->loadCustomer($email);

        // if the admin site has a custom URL, use it
        $urlModel = Mage::getModel('adminhtml/url')->setStore('admin');

        if($customer && $customer->getId()) {
            $info = array(
                'guest' => false,
                'id' => $customer->getId(),
                'name' => $customer->getName(),
                'email' => $customer->getEmail(),
                'active' => (bool)$customer->getIsActive(),
                'admin_url' => $urlModel->getUrl('adminhtml/zendesk/redirect', array('id' => $customer->getId(), 'type' => 'customer')),
                'created' => $customer->getCreatedAt(),
                'dob' => $customer->getDob(),
                'addresses' => array(),
                'orders' => $orders,
            );

            if($billing = $customer->getDefaultBillingAddress()) {
                $info['addresses']['billing'] = $billing->format('text');
            }

            if($shipping = $customer->getDefaultShippingAddress()) {
                $info['addresses']['shipping'] = $shipping->format('text');
            }

        } else {
            if(count($orders) == 0) {
                // The email address doesn't even correspond with a guest customer
                return json_encode(array('success' => false, 'message' => 'Customer does not exist'));
            }

            $info = array(
                'guest' => true,
                'orders' => $orders,
            );
        }

        return json_encode($info);
    }
}
