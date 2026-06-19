# OrderExperienceManagement Module
[![Latest Stable Version](https://poser.pugx.org/spryker-feature/order-experience-management/v/stable.svg)](https://packagist.org/packages/spryker-feature/order-experience-management)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.3-8892BF.svg)](https://php.net/)

Top-level container for order placement features in Spryker B2B commerce.
Currently, implements **Recurring Orders** — schedule-based automatic reordering.

## Recurring Orders

Lets B2B buyers configure a repeating order cadence during checkout. After placement the order is automatically re-placed at the selected interval without manual intervention.
Supports weekly, bi-weekly, monthly, and every-N-weeks cadences with a StateMachine-driven lifecycle, price-lock guardrails, and configurable retry on failure.

## Installation

```bash
composer require spryker-feature/order-experience-management
```

## Documentation

[Spryker Documentation](https://docs.spryker.com)
