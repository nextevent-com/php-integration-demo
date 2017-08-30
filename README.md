# NextEvent PHP SDK Integration Demo

This is a reference integration of NextEvent's PHP SDK in a shopping web application.

It's a loose adaption following the [SDK documentation](http://docs.nextevent.com) and
primarily serves as a showcase.

# Requirements

This project requires PHP 5.4 or higher with session support and [Composer](https://getcomposer.org)
to install the dependencies.

# Installation

In order to run this demo web app, first clone the repo to your webserver:

```
git clone https://github.com/nextevent-com/php-integration-demo.git nextevent_demo
```

Then install the required modules with Composer:

```
cd /path/to/nextevent_demo
composer install --no-dev
```

# Configuration

Before running the app, you need to create a local config file using the
credentials to access the NextEvent API. Please [contact us](http://nextevent.com)
to obtain these credentials.

Then copy the file `config/example.config.php` into `config/config.php`
and adjust the config options according to your environment and using the
API access credentials.

# Run

Since this is a bare-bones PHP web application it's best served with a PHP enabled webserver
like Apache or Nginx. If you don't want to dive into a full webserver setup, simply start
PHP's built-in web server from the shell:

```
cd /path/to/nextevent_demo
php -S 0.0.0.0:8000 -t public/
```

Now point your browser at `http://<your-host-name>:8000` and book your first ticket.

# License

Copyright (C) 2017, NextEvent GmbH, nextevent.com

Published under the [MIT License](https://opensource.org/licenses/MIT), all rights reserved.
