# kitodo-crowdsourcing

A TYPO3 CMS extension that powers the crowdsourcing platform of the
[Württembergische Landesbibliothek (WLB) Stuttgart](https://www.wlb-stuttgart.de/).
It lets volunteers collaboratively enrich and correct the metadata of digitized
library materials, while editors organize and supervise the work through dedicated
backend modules.

## Overview

Digitized works (produced with [Kitodo](https://www.kitodo.org/)) are imported into
the system and grouped into **campaigns**. Registered frontend users pick up
processes from a campaign, edit their metadata against a configurable rule set, and
submit their contributions. All content is indexed in **Apache Solr** to provide
fast search across processes and campaigns. Editors track progress and engagement
via statistics and manage the whole life cycle of campaigns from the TYPO3 backend.

## Features

- **Crowdsourcing workflow** – Frontend users browse campaigns, get assigned
  individual processes, edit metadata, and save their contributions
  (landing page, dashboard, campaign and process detail views).
- **Campaign management** – Backend module to create, edit, publish, close, reopen,
  and delete campaigns and to assign or remove processes.
- **Process import** – Imports digitized works (e.g. from Kitodo) from configurable
  directories, with handling for imported, failed, and archived items.
- **Configurable metadata rules** – Validation and editing behavior is driven by a
  configurable rule set and index field configuration.
- **Solr integration** – Indexing and searching of processes and campaigns via
  a dedicated Solr client, indexer, and searcher.
- **Statistics & tracking** – Backend statistics module plus click/page-hit tracking
  and process history for contributions.
- **CLI commands** – Import processes, (re)build and clear the Solr index, and clean
  up stale processes.
- **User registration & access control** – Frontend user creation and access control
  for editing, integrating `sf-register` and `sr-freecap`.

## Requirements

- TYPO3 CMS **13.4**
- PHP **8.2 – 8.4**
- Apache **Solr**
- PHP extensions: `dom`, `simplexml`

Key dependencies: `solarium/solarium`, `galbar/jsonpath`, `fluidtypo3/vhs`,
`evoweb/sf-register`, `evoweb/extender`, `sjbr/sr-freecap`, `symfony/filesystem`.

## Installation

Install the extension via Composer:

```bash
composer require wlb/crowdsourcing
```

Then activate it in the TYPO3 backend (Admin Tools → Extensions) or via the CLI:

```bash
vendor/bin/typo3 extension:setup
```

## Configuration

The extension is configured through the *Extension Configuration* (Settings →
Extension Configuration → `crowdsourcing`). The most important options are:

- **Import directories** – `toImportDirectoryPath`, `processDirectoryPath`,
  `importedDirectoryPath`, `failedDirectoryPath`, `archiveDirectoryPath`,
  `exportDirectoryPath`.
- **Image directories** – `processImageBaseDirectory`, `processImageDefaultDirectory`,
  `processImageThumbDirectory`.
- **Solr connection** – `solrHost`, `solrPort`, `solrPath`, `solrCore`.
- **General** – `storagePid`, `reportMail`, `rulesetPath`, `gndVerifyUrl`.

## CLI Commands

```bash
# Import processes from the configured import directory
vendor/bin/typo3 crowdsourcing:import

# Rebuild / clear the Solr index
vendor/bin/typo3 crowdsourcing:rebuildIndex
vendor/bin/typo3 crowdsourcing:clearIndex

# Remove stale processes
vendor/bin/typo3 crowdsourcing:cleanupStaleProcesses
```

> Command identifiers follow the definitions in `Configuration/Services.yaml`.

## Backend Modules

The extension registers a *Crowdsourcing* backend module group with submodules for:

- **Campaign** – manage campaigns and their processes
- **Configuration** – manage metadata configuration
- **Statistics** – view contribution and usage statistics

## Third Party Libraries

Bundled frontend libraries and their licenses are listed in
[`extensions/crowdsourcing/THIRD_PARTY_LICENSES.md`](extensions/crowdsourcing/THIRD_PARTY_LICENSES.md).

## License

This extension is released under the GNU General Public License.
See the license headers in the source files for details.

## Credits

Developed by [effective Webwork GmbH](https://effective-webwork.de) for and funded by
the [Württembergische Landesbibliothek Stuttgart](https://www.wlb-stuttgart.de/).
