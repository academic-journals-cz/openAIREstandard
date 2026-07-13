# OpenAIRE Plugin standard

## About
The plugin is based mainly on the plugin https://github.com/ojsde/openAIRE.

It is an alternation of the XML generator of the plugin and creating now metadata_prefix "oai_openaire". Now it completly fullfills the guideline v4 for literature from OpenAIRE https://openaire-guidelines-for-literature-repository-managers.readthedocs.io/en/v4.0.0/application_profile.html. You can use validator for this XML. It follows the OpenAIRE XSD rules and can be scaled by this rules more: https://github.com/openaire/guidelines-literature-repositories/tree/master/schemas/4.0

As of version 1.0.2-1, this plugin also provides the JATS XML metadata format (metadata_prefix "oai_openaire_jats") that was previously offered by the separate https://github.com/ojsde/openAIRE plugin. Enabling this plugin now covers both formats. The two plugins should not be enabled at the same time on the same journal: both independently add "Resource Type"/"Audience" fields to the section settings form, so enabling both would show duplicate fields there.

## License
This plugin is licensed under the GNU General Public License v3. See the file LICENSE for the complete terms of this license.

## System Requirements
OJS 3.5.0.x.
PHP 8.2 or greater.

For OJS 3.3.0/3.4.0, use the corresponding earlier release listed under "Latest Versions" below.

## Latest Versions
- Version 1.0.2-1 – Merged in the openAIRE (JATS) plugin's oai_openaire_jats format, so this plugin now serves both oai_openaire and oai_openaire_jats
- Version 1.0.2-0 – Support for OJS 3.5.0
- Version 1.0.1-1 – Support for OJS 3.4.0
- Version 1.0.0-1 – Support for OJS 3.3.0

## Legacy version
- Version 3.2.1.0 – Support for OJS 3.2.1

## Example
https://cyberpsychology.eu/oai?verb=ListRecords&metadataPrefix=oai_openaire

The JATS format is available the same way, at the same OAI endpoint, using `metadataPrefix=oai_openaire_jats` instead.

# Credit
---------------
This plugin was developed at the [Masaryk University Press - Munipress](https://www.press.muni.cz), as part of its active participation in the [Craft-OA project](https://www.craft-oa.eu/).

The development was initiated, coordinated, and technically supported by Munipress.

# Academic-journals.cz Workspace
--------------
Academic-journals.cz is a collaborative workspace for the development and management of the Academic Journals platform for Czech scholarly journals. The project aims to support Czech academic publishers and editorial teams by providing shared infrastructure and tools for publishing and managing academic journals.

The project is based on an open memorandum of cooperation between **Charles University** and **Palacký University Olomouc**, under the leadership of **Karolinum Press** and **Palacký University Press**.

The memorandum establishes a shared framework for cooperation in the field of academic journal publishing infrastructure and services. It is designed as an open initiative, allowing other universities and academic institutions to join and participate in the development of the platform.

Technical garant for the project is **Radek Gomola**.