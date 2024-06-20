[![Unit Tests](https://git.formassembly.com/Formassembly/connector-core/actions/workflows/unit-tests.yml/badge.svg)](https://git.formassembly.com/Formassembly/connector-core/actions/workflows/unit-tests.yml) ![Code Coverage Badge](../image-data/badge-coverage.svg?raw=1) [![Documentation Generator](https://git.formassembly.com/Formassembly/connector-core/actions/workflows/documentation.yml/badge.svg)](https://git.formassembly.com/Formassembly/connector-core/actions/workflows/documentation.yml)

## Connector Core Library 




This is the connector runtime library, available as a PHP Composer package. 

See: [Connector Design](https://formassembly.atlassian.net/wiki/spaces/ENG/pages/2658074625/Connector+Design) in Confluence.

See also: [Documentation](https://pages.git.formassembly.com/Formassembly/connector-core/)


### Integration Libraries 

Integrations with specific systems are hosted in separate repositories:

* [`formassembly/integration-workflow`](https://git.formassembly.com/Formassembly/connector-integration-workflow) - Two-way data flow with FormAssembly's Workflow.

* [`formassembly/integration-googlesheets`](https://git.formassembly.com/Formassembly/connector-integration-googlesheets) - Two-way data flow with Google Sheets.

* [`formassembly/integration-googledrive`](https://git.formassembly.com/Formassembly/connector-integration-googledrive) - Send files to Google Drive.
    
New integrations can be created using [this template](https://git.formassembly.com/Formassembly/connector-integration-template).

### Development ###

Run Tests:
```
composer run tests
```

with coverage:
```
composer run test-coverage
```
