---
description: >-
  Getting started with Laravel Revisor is a easy as. Follow these simple steps
  to add Revisor to your Laravel project.
---

# Installation

***

### Requirements[​](https://prism.echolabs.dev/installation.html#requirements) <a href="#requirements" id="requirements"></a>

Before we dive in, make sure your project meets these requirements:

* PHP 8.3 or higher
* Laravel 11.0 or higher

***

### Step 1: Composer Installation <a href="#step-1-composer-installation" id="step-1-composer-installation"></a>

First, let's add Revisor to your project using Composer. Open your terminal, navigate to your project directory, and run:

```bash
composer require indra/laravel-revisor
```

***

### Step 2: Publish the Configuration <a href="#step-2-publish-the-configuration" id="step-2-publish-the-configuration"></a>

Revisor comes with a configuration file that you may want to customise. Publish it to your config directory by running:

```bash
php artisan vendor:publish --tag="revisor-config"
```

### That's it\![​](https://prism.echolabs.dev/installation.html#that-s-it) <a href="#that-s-it" id="that-s-it"></a>

You've successfully installed Revisor in your Laravel project. You're now ready to start versioning and publishing your Eloquent Model records with ease.
