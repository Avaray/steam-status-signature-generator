# Personal Steam Signature Generator

<!-- Remember to change branches in badges after PR to main -->

[![PHP 5.6](https://github.com/Avaray/personal-steam-signature/actions/workflows/test-php5.yml/badge.svg?branch=making-it-modern)](https://github.com/Avaray/personal-steam-signature/actions/workflows/test-php5.yml) [![PHP 7.4](https://github.com/Avaray/personal-steam-signature/actions/workflows/test-php7.yml/badge.svg?branch=making-it-modern)](https://github.com/Avaray/personal-steam-signature/actions/workflows/test-php7.yml)

This [PHP](https://www.php.net/) script generates [PNG](https://en.wikipedia.org/wiki/PNG) image with status of user on [Steam](https://store.steampowered.com/).  
This is personal script for just one user.

Use [CRON](https://cronitor.io/guides) to execute this script.

# Requirements

- [PHP](https://www.php.net/) (version not specified yet; I used 8.3.1, but it should work on 7.X too).
- [GD](https://github.com/libgd/libgd) extension (version 2.X). You probably have it in your PHP extensions directory. You will need to enable it. Check this [link](https://stackoverflow.com/questions/2283199/enabling-installing-gd-extension-without-gd) for more information.
- [CURL](https://curl.se/) extension. Same case as with GD extension. Probably all you need to do is to enable it in your PHP configuration file.

# Configuration

You need to modify `config.php` file.

```php
<?php
return [
    'steam_id' => 'XXXXXXXXXXXXXXXXX',
    'steam_api_key' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX',
    'capitalized_personaname' => false,
];
```

# TODO's

- [ ] Re-write the entire script.
- [ ] Possibility to pass data in URL (use [parse_ur](https://www.php.net/manual/en/function.parse-url.phpl) for that).
