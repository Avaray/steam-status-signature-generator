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

- [PHP](https://www.php.net/) 5.3 or higher. Check badges above for PHP versions.
- [GD](https://github.com/libgd/libgd) extension. Depending on your server configuration, this will either be enabled or disabled. Check this [link](https://stackoverflow.com/questions/2283199/enabling-installing-gd-extension-without-gd) for more information on how to enable it.
- [CURL](https://curl.se/) extension. Same case as with GD extension. If it's not enabled, enable it.

Except that you need [Steam API Key](https://steamcommunity.com/dev/apikey). You can get it for free if your account [is not limited](https://help.steampowered.com/en/faqs/view/71D3-35C2-AD96-AA3A).

## Installation

You can [clone](https://git-scm.com/docs/git-clone/en) this repository using [Git](https://git-scm.com/) or [GitHub Desktop](https://github.com/apps/desktop) or any other [Git client](https://git-scm.com/downloads/guis).

```bash
git clone https://github.com/Avaray/personal-steam-signature.git
```

Or you can download this repository as a [ZIP archive](https://github.com/Avaray/personal-steam-signature/archive/refs/heads/master.zip) and extract it to your desired location.

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

> In this case you need to split `ids` with comma `,`.

```bash
php sig.php key=ABCD ids=1234,5678
```

### By passing variables as URL parameters.

> This method requires properly configured web server.  
> If you will keep this script in public directory, make sure you are not exposing your `config.json` file.

```
https://wow.com/?key=ABCD&ids=1234,5678
```

## List of options for `config.json` file

| Key                 | Value type | Default | Required | Description                                                                                                  |
| ------------------- | ---------- | :-----: | :------: | ------------------------------------------------------------------------------------------------------------ |
| `key`               | `string`   |  `""`   | **Yes**  | Your Steam [API Key](https://steamcommunity.com/dev/apikey)                                                  |
| `ids`               | `array`    | `[""]`  | **Yes**  | List of [Community IDs](https://developer.valvesoftware.com/wiki/SteamID) (SteamID64 only)                   |
| `timezone`          | `string`   |  `""`   |   _No_   | Your [TimeZone](https://www.php.net/manual/en/timezones.europe.php) (for time correction in logs)            |
| `avatar`            | `boolean`  | `true`  |   _No_   | Include profile image                                                                                        |
| `capitalize_name`   | `boolean`  | `false` |   _No_   | Name will be capitalized                                                                                     |
| `capitalize_status` | `boolean`  | `false` |   _No_   | Status will be capitalized                                                                                   |
| `font_primary`      | `string`   |  `""`   |   _No_   | Name of the font file in `fonts` directory                                                                   |
| `font_secondary`    | `string`   |  `""`   |   _No_   | Name of the font file in `fonts` directory                                                                   |
| `input_file`        | `string`   |  `""`   |   _No_   | Path to the file with list of IDs.<br>Supported formats: `.txt`, `.json`                                     |
| `db_file`           | `string`   |  `""`   |   _No_   | Path to the JSON file where statuses will be saved                                                           |
| `output_dir`        | `string`   |  `""`   |   _No_   | Path to the directory where images will be saved. If not set, images will be saved in the same directory     |
| `self_running`      | `boolean`  | `false` |   _No_   | If set to `true`, script will run itself every `X` seconds. You need handle crashes and restarts by yourself |
| `interval`          | `integer`  |   `0`   |   _No_   | Interval in seconds for `self_running` option. <br>By default interval is calculated automatically           |

You can check configuration flow [here](FLOW.md) to see how the script searches for configuration.

### Loading IDs from file

If you want to load IDs from [JSON](https://www.w3schools.com/js/js_json_arrays.asp) file, that file should contain one valid array of IDs.  
In `Text` file you can separate IDs by anything you want (new line, space, symbol, letter).

## Notes

Keep in mind that there is limit of `100,000` requests per day for Steam API. If you are planning to use this script for a large number of users, you should calculate how often you can run this script. Maximum number of users to check in one request is `100`. So if you have `1000` users, you will need to make `10` requests in one run. So, in this example you can perform entire operation every `≈ 8.64 seconds` to not exceed the limit.

Dealing with many users can be heavy for your server. If you are planning to use this script as part of a public service, you should consider using good hosting provider. Free service providers may ban your account if you exceed their limits.

When you run the script for the first time, it will generate statuses for all IDs. This can take a lot of time and machine resources. On next runs, it will only generate them for people whose statuses have changed.

## TODO's

- [ ] Re-write the entire script.
- [ ] Support for multiple users (up to 100 ...and maybe more). Loading list from file.
- [ ] Automatic delay if previous request was made less than X seconds ago.
- [ ] Workflow for generating example images.
- [x] Cleanups for IDs that are not used anymore.
- [ ] Customizations for individual IDs.
