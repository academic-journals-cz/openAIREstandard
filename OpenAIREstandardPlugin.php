<?php

/**
 * @file plugins/generic/openAIREstandard/OpenAIREstandardPlugin.inc.php
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class OpenAIREstandardPlugin
 * @ingroup plugins_generic_openAIREstandard
 *
 * @brief OpenAIREstandard plugin class
 */

namespace APP\plugins\generic\openAIREstandard;

use APP\core\Application;
use APP\facades\Repo;
use PKP\db\DAORegistry;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;
use APP\plugins\generic\openAIREstandard\OAIMetadataFormatPlugin_OpenAIREstandard;
use APP\plugins\generic\openAIREstandard\OpenAIREstandardGatewayPlugin;

class OpenAIREstandardPlugin extends GenericPlugin {

    /**
     * @copydoc Plugin::register()
     */
    function register($category, $path, $mainContextId = null) {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled($mainContextId)) {
            PluginRegistry::register('oaiMetadataFormats', new OAIMetadataFormatPlugin_OpenAIREstandard($this), $this->getPluginPath());
            PluginRegistry::register('gateways', new OpenAIREstandardGatewayPlugin($this), $this->getPluginPath());

            # Handle COAR resource types in section forms
            Hook::add('Schema::get::section', [$this, 'addToSchema']);
            Hook::add('Templates::Manager::Sections::SectionForm::AdditionalMetadata', array($this, 'addSectionFormFields'));
            Hook::add('sectionform::initdata', array($this, 'initDataSectionFormFields'));
            Hook::add('sectionform::readuservars', array($this, 'readSectionFormFields'));
            Hook::add('sectionform::execute', array($this, 'executeSectionFormFields'));

            $this->_registerTemplateResource();
        }
        return $success;
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    function getDisplayName() {
        return __('plugins.generic.openAIREstandard.displayName');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    function getDescription() {
        return __('plugins.generic.openAIREstandard.description');
    }

    /**
     * Extend the section entity's schema with an resourceType property
     */
    public function addToSchema(string $hookName, array $args) {
        $schema = $args[0];/** @var stdClass */
        $schema->properties->resourceType = (object) [
                    'type' => 'string',
                    'apiSummary' => true,
                    'multilingual' => false,
                    'validation' => ['nullable']
        ];
        $schema->properties->audience = (object) [
                    'type' => 'string',
                    'apiSummary' => true,
                    'multilingual' => false,
                    'validation' => ['nullable']
        ];
        return false;
    }

    /**
     * Add fields to the section editing form
     *
     * @param $hookName string `Templates::Manager::Sections::SectionForm::AdditionalMetadata`
     * @param $args array [
     * 		@option array [
     * 				@option name string Hook name
     * 				@option sectionId int
     * 		]
     * 		@option Smarty
     * 		@option string
     * ]
     * @return bool
     */
    public function addSectionFormFields($hookName, $args) {
        $smarty = & $args[1];
        $output = & $args[2];
        $smarty->assign('resourceTypeOptions', $this->_getResourceTypeOptions());
        $smarty->assign('audienceOptions', $this->_getAudienceOptions());
        $output .= $smarty->fetch($this->getTemplateResource('controllers/grids/settings/section/form/sectionFormAdditionalFields.tpl'));
        return false;
    }

    /**
     * Initialize data when form is first loaded
     *
     * @param $hookName string `sectionform::initData`
     * @parram $args array [
     * 		@option SectionForm
     * ]
     */
    public function initDataSectionFormFields($hookName, $args) {
        $sectionForm = $args[0];
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : CONTEXT_ID_NONE;
        $section = Repo::section()->get($sectionForm->getSectionId());
        if ($section) {
            $sectionForm->setData('resourceType', $section->getData('resourceType'));
            $sectionForm->setData('audience', $section->getData('audience'));
        }
    }

    /**
     * Read user input from additional fields in the section editing form
     *
     * @param $hookName string `sectionform::readUserVars`
     * @parram $args array [
     * 		@option SectionForm
     * 		@option array User vars
     * ]
     */
    public function readSectionFormFields($hookName, $args) {
        $sectionForm = & $args[0];
        $request = Application::get()->getRequest();
        $sectionForm->setData('resourceType', $request->getUserVar('resourceType'));
        $sectionForm->setData('audience', $request->getUserVar('audience'));
    }

    /**
     * Save additional fields in the section editing form
     *
     * @param $hookName string `sectionform::execute`
     * @param $args array
     *
     */
    public function executeSectionFormFields($hookName, $args) {
        $sectionForm = $args[0];
        $resourceType = $sectionForm->getData('resourceType') ? $sectionForm->getData('resourceType') : '';
        if (!empty($resourceType)) {
            $section = Repo::section()->get($sectionForm->getSectionId());
            $section->setData('resourceType', $resourceType);
            Repo::section()->edit($section, []);
        }
        
        $audience = $sectionForm->getData('audience') ? $sectionForm->getData('audience') : '';
        if (!empty($audience)) {
            $section = Repo::section()->get($sectionForm->getSectionId());
            $section->setData('audience', $audience);
            Repo::section()->edit($section, []);
        }
    }

    /**
     * Get a COAR Resource Type by URI. If $uri is null return all.
     * @param $uri string
     * @return mixed
     */
    function _getCoarResourceType($uri = null) {
        $resourceTypes = array(
            'http://purl.org/coar/resource_type/c_6501' => 'journal article',
            'http://purl.org/coar/resource_type/c_2df8fbb1' => 'research article',
            'http://purl.org/coar/resource_type/c_dcae04bc' => 'review article',
            'http://purl.org/coar/resource_type/c_beb9' => 'data paper',
            'http://purl.org/coar/resource_type/c_7bab' => 'software paper',
            'http://purl.org/coar/resource_type/c_b239' => 'editorial',
            'http://purl.org/coar/resource_type/c_545b' => 'letter to the editor',
            'http://purl.org/coar/resource_type/c_93fc' => 'report',
            'http://purl.org/coar/resource_type/c_efa0' => 'review',
            'http://purl.org/coar/resource_type/c_ba08' => 'book review',
            'http://purl.org/coar/resource_type/c_26e4' => 'interview',
            'http://purl.org/coar/resource_type/c_8544' => 'lecture',
            'http://purl.org/coar/resource_type/c_5794' => 'conference paper',
            'http://purl.org/coar/resource_type/c_46ec' => 'thesis',
            'http://purl.org/coar/resource_type/c_8042' => 'working paper',
            'http://purl.org/coar/resource_type/c_816b' => 'preprint',
            'http://purl.org/coar/resource_type/c_1843' => 'other'
        );
        if ($uri) {
            return $resourceTypes[$uri];
        } else {
            return $resourceTypes;
        }
    }

    /**
     * Get an associative array of all COAR Resource Type Genres for select element
     * (Includes default '' => "Choose One" string.)
     * @return array resourceTypeUri => resourceTypeLabel
     */
    function _getResourceTypeOptions() {
        $resourceTypeOptions = $this->_getCoarResourceType(null);
        $chooseOne = __('common.chooseOne');
        $chooseOneOption = array('' => $chooseOne);
        $resourceTypeOptions = $chooseOneOption + $resourceTypeOptions;
        return $resourceTypeOptions;
    }

    /**
     * Get an array of Audience tapes for select element
     * (Includes default '' => "Choose One" string.)
     * @return array resourceTypeUri => resourceTypeLabel
     */
    function _getAudienceOptions() {
        $audience = array(
            '' => __('common.chooseOne'),
            'Administrators' => 'Administrators',
            'Community Groups' => 'Community Groups',
            'Counsellors' => 'Counsellors',
            'Federal Funds Recipients and Applicants' => 'Federal Funds Recipients and Applicants',
            'Librarians' => 'Librarians',
            'News Media' => 'News Media',
            'Other' => 'Other',
            'Parents and Families' => 'Parents and Families',
            'Policymakers' => 'Policymakers',
            'Researchers' => 'Researchers',
            'School Support Staff' => 'School Support Staff',
            'Student Financial Aid Providers' => 'Student Financial Aid Providers',
            'Students' => 'Students',
            'Teachers' => 'Teachers'
        );
        return $audience;
    }
}
