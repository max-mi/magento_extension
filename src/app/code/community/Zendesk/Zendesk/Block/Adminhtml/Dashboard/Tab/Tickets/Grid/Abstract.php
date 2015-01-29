<?php

/**
 * Copyright 2013 Zendesk.
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

abstract class Zendesk_Zendesk_Block_Adminhtml_Dashboard_Tab_Tickets_Grid_Abstract extends Mage_Adminhtml_Block_Widget_Grid {
    protected $_page;
    protected $_limit;
    protected $_viewId;
    
    protected $_defaultLimit    = 20;
    protected $_defaultPage     = 1;
    protected $_defaultSort     = 'created_at';
    protected $_defaultDir      = 'desc';
    
    protected abstract function _getCollection($collection);
    
    protected function _getCollectionModel() {
        return Mage::getModel('zendesk/resource_tickets_collection');
    }
    
    public function setViewId($id = null) {
        $this->_viewId = (is_null($id) ? uniqid() : $id);
    }
    
    public function __construct($attributes = array()) {
        parent::__construct($attributes);
        
        $this->setTemplate('zendesk/widget/grid.phtml');
        
        $this->_emptyText   = Mage::helper('zendesk')->__('No tickets found');
    }
    
    protected function _construct() {
        $this->setId('zendesk_tab_tickets_grid_' . $this->_viewId);
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
        
        $this->_page    = (int) $this->getParam( $this->getVarNamePage(), $this->_defaultPage);
        $this->_limit   = (int) $this->getParam( $this->getVarNameLimit(), $this->_defaultLimit);
    }
    
    protected function _preparePage() {
        parent::_preparePage();
        
        $this->_page    = (int) $this->getParam( $this->getVarNamePage(), $this->_defaultPage);
        $this->_limit   = (int) $this->getParam( $this->getVarNameLimit(), $this->_defaultLimit);
    }
    
    protected function _prepareCollection() {
        if( ! $this->getCollection() ) {
            $collection     = $this->_getCollectionModel();
            $filter         = $this->getParam('filter');
            $filterData     = Mage::helper('adminhtml')->prepareFilterString($filter);

            foreach($filterData as $fieldName => $value) {
                $collection->addFieldToFilter($fieldName, $value);
            }

            $this->setDefaultLimit( $this->getParam('limit', $this->_defaultLimit) );
            $this->setCollection( $this->_getCollection($collection) );
        }
        
        return parent::_prepareCollection();
    }
    
    protected function addColumnBasedOnType($index, $title, $filter = false, $sortable = true) {
        $column = array(
            'header'    => Mage::helper('zendesk')->__($title),
            'sortable'  => $sortable,
            'filter'    => $filter,
            'index'     => $index,
            'type'      => $this->getColumnType($index),
        );
        
        $renderer = $this->getColumnRenderer($index);
        
        if($renderer !== null) {
            $column['renderer'] = $renderer;
        }
        
        $this->addColumn($index, $column);
    }
    
    protected function getColumnType($index) {
        switch($index) {
            case 'created_at':
            case 'created':
            case 'requested':
            case 'updated_at':
            case 'updated':
                return 'datetime';
            default:
                return 'text';
        }
    }
    
    protected function getColumnRenderer($index) {
        switch($index) {
            case 'requester':
            case 'assignee':
                return 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_user';
            case 'subject':
                return 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_action';
            case 'group':
                return 'zendesk/adminhtml_dashboard_tab_tickets_grid_renderer_group';
            default:
                return null;
        }
    }
    
    protected function getGridParams() {
        return array(
            'page'          => $this->_page,
            'per_page'      => $this->_limit,
            'sort_order'    => $this->getParam( $this->getVarNameDir(), $this->_defaultDir),
            'sort_by'       => $this->getParam( $this->getVarNameSort(), $this->_defaultSort),
        );
    }
    
}