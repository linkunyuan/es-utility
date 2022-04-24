My utility for easyswoole
==========

Quick Start
-----------

最新版已迁移至[https://github.com/wonder-game/es-utility](https://github.com/wonder-game/es-utility)
-----------

Install the library using [composer](https://getcomposer.org):

    php composer.phar require linkunyuan/es-utility

Import traits and run:

_Controller_
```php
<?php
use Linkunyuan\EsUtility\Traits\LamController;

class MyClass
{
	use LamController;
    
	// here are some methods from LamController ....
}

```
_Model_
```php
<?php
use Linkunyuan\EsUtility\Traits\LamModel;

class MyClass
{
	use LamModel;
    
	// here are some methods from LamController ....
}

```
