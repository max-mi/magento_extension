<?php
/**
 * Copyright 2015 Zendesk
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
require_once 'Mage/Oauth/controllers/Adminhtml/Oauth/AuthorizeController.php';
class Zendesk_Zendesk_Oauth_Adminhtml_Oauth_AuthorizeController extends Mage_Oauth_Adminhtml_Oauth_AuthorizeController
{
    /**
     * Init confirm page
     *
     * @param bool $simple
     * @return Mage_Oauth_Adminhtml_Oauth_AuthorizeController
     */
    protected function _initConfirmPage($simple = false)
    {
        /** @var $helper Mage_Oauth_Helper_Data */
        $helper = Mage::helper('oauth');

        /** @var $session Mage_Admin_Model_Session */
        $session = Mage::getSingleton($this->_sessionName);

        /** @var $user Mage_Admin_Model_User */
        $user = $session->getData('user');
        if (!$user) {
            $session->addError($this->__('Please login to proceed authorization.'));
            $url = $helper->getAuthorizeUrl(Mage_Oauth_Model_Token::USER_TYPE_ADMIN);
            $app_guid = $this->getRequest()->getParam('app_guid', null);
            $url = $app_guid ? $url . "&app_guid=".$app_guid : $url;
            $origin   = $this->getRequest()->getParam('origin', null);
            $url = $origin ? $url."&origin=".$origin : $url;
            $this->_redirectUrl($url);
            return $this;
        }

        $this->loadLayout();

        /** @var $block Mage_Oauth_Block_Adminhtml_Oauth_Authorize */
        $block = $this->getLayout()->getBlock('content')->getChild('oauth.authorize.confirm');
        $block->setIsSimple($simple);

        try {
            /** @var $server Mage_Oauth_Model_Server */
            $server = Mage::getModel('oauth/server');

            $token = $server->authorizeToken($user->getId(), Mage_Oauth_Model_Token::USER_TYPE_ADMIN);

            if (($callback = $helper->getFullCallbackUrl($token))) { //false in case of OOB
                $app_guid = $this->getRequest()->getParam('app_guid', null);
                $callback = $app_guid ? $callback . "&app_guid=".$app_guid : $callback;
                $origin = $this->getRequest()->getParam('origin', null);
                $callback = $origin ? $callback . "&origin=".$origin : $callback;
                $this->getResponse()->setRedirect($callback . ($simple ? '&simple=1' : ''));
                return $this;
            } else {
                $block->setVerifier($token->getVerifier());
                $session->addSuccess($this->__('Authorization confirmed.'));
            }
        } catch (Mage_Core_Exception $e) {
            $block->setHasException(true);
            $session->addError($e->getMessage());
        } catch (Exception $e) {
            $block->setHasException(true);
            $session->addException($e, $this->__('An error occurred on confirm authorize.'));
        }

        $this->_initLayoutMessages($this->_sessionName);
        $this->renderLayout();

        return $this;
    }
}
