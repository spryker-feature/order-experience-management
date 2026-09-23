# OrderExperienceManagement Module

[![Latest Stable Version](https://poser.pugx.org/spryker-feature/order-experience-management/v/stable.svg)](https://packagist.org/packages/spryker-feature/order-experience-management)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)

Top-level container for order placement features in Spryker B2B commerce.
Implements **Recurring Orders** (schedule-based automatic reordering) and the **Orders Backend API**
(read, list, intake, OMS transitions and comments).

## Recurring Orders

Lets B2B buyers configure a repeating order cadence during checkout. After placement the order is automatically re-placed at the selected interval without manual intervention.
Supports weekly, bi-weekly, monthly, and every-N-weeks cadences with a StateMachine-driven lifecycle, price-lock guardrails, and configurable retry on failure.

## Orders Backend API

Serves placed orders to Back Office and system integrations over API Platform, behind `ROLE_BACK_OFFICE_USER`:
read and list orders, create one via order intake, fire OMS events on individual line items, and read or
add order comments. Resource contracts live in [`resources/api/backend/`](resources/api/backend/).

## Installation

```bash
composer require spryker-feature/order-experience-management
```

## Documentation

[Spryker Documentation](https://docs.spryker.com)
