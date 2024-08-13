# 😎 Personal Steam Signature Generator

<!-- Remember to change branches in badges after PR to main -->

[![5.3](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP5.3.yml/badge.svg?branch=making-it-modern)](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP5.3.yml)
[![5.4](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP5.4.yml/badge.svg?branch=making-it-modern)](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP5.4.yml)
[![5.6](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP5.6.yml/badge.svg?branch=making-it-modern)](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP5.6.yml)
[![7.0](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP7.0.yml/badge.svg?branch=making-it-modern)](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP7.0.yml)
[![7.4](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP7.4.yml/badge.svg?branch=making-it-modern)](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP7.4.yml)
[![8.0](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP8.0.yml/badge.svg?branch=making-it-modern)](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP8.0.yml)
[![8.4](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP8.4.yml/badge.svg?branch=making-it-modern)](https://github.com/Avaray/personal-steam-signature/actions/workflows/test_PHP8.4.yml)

This [PHP](https://www.php.net/) script generates [PNG](https://en.wikipedia.org/wiki/PNG) image with status of [Steam](https://store.steampowered.com/) user.  
The width of the image adjusts to the length of the username or the length of the game title the user is currently playing. The height of the image is fixed.  
Where you place the generated image is entirely up to you. In the past, people used to add such status images to their signatures on [internet forums](https://en.wikipedia.org/wiki/Internet_forum).

## Requirements

- [PHP](https://www.php.net/) 5.4 or higher. Check badges above for PHP versions.
- [GD](https://github.com/libgd/libgd) extension. Depending on your server configuration, this will either be enabled or disabled. Check this [link](https://stackoverflow.com/questions/2283199/enabling-installing-gd-extension-without-gd) for more information on how to enable it.
- [CURL](https://curl.se/) extension. Same case as with GD extension. If it's not enabled, enable it.

Except that you need [Steam API Key](https://steamcommunity.com/dev/apikey). You can get it for free if your account [is not limited](https://help.steampowered.com/en/faqs/view/71D3-35C2-AD96-AA3A).

## Installation

You can [clone](https://git-scm.com/docs/git-clone/en) this repository using [Git](https://git-scm.com/) or [GitHub Desktop](https://github.com/apps/desktop) or any other [Git client](https://git-scm.com/downloads/guis).

```bash
git clone https://github.com/Avaray/personal-steam-signature.git
```

Or you can download this repository as a [ZIP archive](https://github.com/Avaray/personal-steam-signature/archive/refs/heads/master.zip) and extract it to your desired location.

## Usage

Keep in mind that there is limit of `100,000` requests per day for Steam API.  
If you are planning to use this script for a large number of users, you should calculae how many requests you will make per day. Maximum number of users in one request is `100`. So if you have `1000` users, you need to launch this script `10` times, splitting users into groups of `100`. That way you will make `10` requests to Steam API. So, you can perform entire operation every `≈ 8.6 second` to not exceed the limit.

## Configuration

Basically all you need is to provide [Steam API Key](https://steamcommunity.com/dev) and at least one [Steam ID](https://developer.valvesoftware.com/wiki/SteamID) to get started.  
You can do that in three ways.

### By editing `config.json` file.

```json
{
  "key": "ABCD",
  "ids": ["1234", "5678"]
}
```

### By passing variables as arguments.

```bash
php sig.php key=ABCD ids=1234,5678
```

### By passing variables as URL parameters.

> This method requires properly configured web server.

```bash
https://wow.com/?key=ABCD&ids=1234,5678
```

## List of options for `config.json` file

| Key                 | Value type | Default | Required | Description                                                                                                |
| ------------------- | ---------- | :-----: | :------: | ---------------------------------------------------------------------------------------------------------- |
| `key`               | `string`   |  `""`   | **Yes**  | Your Steam [API Key](https://steamcommunity.com/dev/apikey)                                                |
| `ids`               | `array`    | `[""]`  | **Yes**  | List of [Community ID's](https://developer.valvesoftware.com/wiki/SteamID)                                 |
| `timezone`          | `string`   |  `""`   |   _No_   | Your [TimeZone](https://www.php.net/manual/en/timezones.europe.php) (set if your machine shows wrong time) |
| `avatar`            | `boolean`  | `true`  |   _No_   | Include profile image                                                                                      |
| `capitalize_name`   | `boolean`  | `false` |   _No_   | Name will be capitalized                                                                                   |
| `capitalize_status` | `boolean`  | `false` |   _No_   | Status will be capitalized                                                                                 |
| `font_primary`      | `string`   |  `""`   |   _No_   | Name of the font file in `fonts` directory                                                                 |
| `font_secondary`    | `string`   |  `""`   |   _No_   | Name of the font file in `fonts` directory                                                                 |

You can check configuration flow [here](FLOW.md) to see how the script searches for configuration.

## # TODO's

- [ ] Re-write the entire script.
- [ ] Support for multiple users (up to 100 ...and maybe more).
- [ ] Workflow for generating example images.
