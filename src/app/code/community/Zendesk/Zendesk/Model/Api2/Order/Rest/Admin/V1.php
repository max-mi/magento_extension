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

class Zendesk_Zendesk_Model_Api2_Order_Rest_Admin_V1 extends Zendesk_Zendesk_Model_Api2_Customer {
    
   /**
     * Retrieve order.
     *
     * @return array
     */
    protected function _retrieveCollection()
    {
        $sections = explode('/', trim($this->getRequest()->getPathInfo(), '/'));
        $orderId = $sections[3];

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        if(!$order && !$order->getId()) {
            return array('success' => false, 'message' => 'Order does not exist');
        }

        $info = Mage::helper('zendesk')->getOrderDetail($order);

        return $info;
    }
}
