{**
 * templates/controllers/grid/settings/section/form/sectionFormAdditionalFields.tpl
 *
 * Copyright (c) 2014-2024 Simon Fraser University
 * Copyright (c) 2003-2024 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @brief Add fields for COAR Resource Types Vocabulary
 *
 *}
<div style="clear:both;">
	{fbvFormSection title="plugins.generic.openAIREstandard.resourceType.title" for="resourceType" inline=true}
		{fbvElement type="select" id="resourceType" from=$resourceTypeOptions selected=$resourceType label="plugins.generic.openAIREstandard.resourceType.description" translate=false}
	{/fbvFormSection}
        {fbvFormSection title="plugins.generic.openAIREstandard.audience.title" for="audience" inline=true}
		{fbvElement type="select" id="audience" from=$audienceOptions selected=$audience label="plugins.generic.openAIREstandard.audience.description" translate=false}
	{/fbvFormSection}
</div>
