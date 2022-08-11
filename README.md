# NBP Exchange Rates - EspoCRM extension

[![GitHub release (latest by date)](https://img.shields.io/github/v/release/dubas-pro/ext-nbp-exchange-rates)](https://github.com/dubas-pro/ext-nbp-exchange-rates/releases/latest)
[![EspoCRM](https://img.shields.io/badge/espocrm-%3E%3D7.0-blue)](#dubas-extension-for-espocrm)
[![PHP](https://img.shields.io/badge/php-%3E%3D7.4-blue)](#dubas-extension-for-espocrm)
[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)

Automatically update your EspoCRM exchange rate set by the National Bank of Poland.

## Before you start

The extension retrieves **average** exchange rates of foreign currencies in Polish złoty (the **"zloty"**; code: **PLN**) announced by the National Bank of Poland (the **"NBP"**) as of the **LAST BUSINESS DAY**.

Polish regulations stipulate that income or costs expressed in foreign currencies shall be converted into zlotys at the average exchange rate of foreign currencies announced by the NBP on the last business day preceding the date on which the tax obligation arises.

## Requirements

- EspoCRM 7.0 or later;
- PHP 7.4 or later;

## Getting started

1. [Download](https://github.com/dubas-pro/ext-nbp-exchange-rates/releases/latest) and install the extension.
2. At Administration > Currency, add desired currencies to "Currency List" and set **PLN** as the "Base Currency" (will not work otherwise).

## Documentation

The documentation (if any) is available [here](docs/README.md).

## License

Please see [License File](LICENSE) for more information.
