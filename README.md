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

- [PHP](https://www.php.net/) 5.3 or higher. Badges above currently shows [Lint](<https://en.wikipedia.org/wiki/Lint_(software)>) status of `php -l`.
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

### API Key in Environment Variable

You can also set your API Key as `STEAM_API_KEY` environment variable. Script will read it.

## List of options for `config.json` file

| Key                 | Value<br>Type | Default |  Required  | Description                                                                                                  |
| ------------------- | :-----------: | :-----: | :--------: | ------------------------------------------------------------------------------------------------------------ |
| `key`               |   `string`    |  `""`   | ✅ **Yes** | Your Steam [API Key](https://steamcommunity.com/dev/apikey)                                                  |
| `ids`               |    `array`    | `[""]`  | ✅ **Yes** | List of [Community IDs](https://developer.valvesoftware.com/wiki/SteamID) (SteamID64 only)                   |
| `timezone`          |   `string`    |  `""`   |  ❌ _No_   | Your [TimeZone](https://www.php.net/manual/en/timezones.europe.php) (for time correction in logs)            |
| `avatar`            |   `boolean`   | `true`  |  ❌ _No_   | Include profile image                                                                                        |
| `capitalize_name`   |   `boolean`   | `false` |  ❌ _No_   | Name will be capitalized                                                                                     |
| `capitalize_status` |   `boolean`   | `false` |  ❌ _No_   | Status will be capitalized                                                                                   |
| `font_primary`      |   `string`    |  `""`   |  ❌ _No_   | Name of the font file in `fonts` directory                                                                   |
| `font_secondary`    |   `string`    |  `""`   |  ❌ _No_   | Name of the font file in `fonts` directory                                                                   |
| `input_file`        |   `string`    |  `""`   |  ❌ _No_   | Path to the file with list of IDs.<br>Supported formats: all                                                 |
| `db_file`           |   `string`    |  `""`   |  ❌ _No_   | Path to the JSON file where statuses will be saved                                                           |
| `output_dir`        |   `string`    |  `""`   |  ❌ _No_   | Path to the directory where images will be saved. If not set, images will be saved in the same directory     |
| `self_running`      |   `boolean`   | `false` |  ❌ _No_   | If set to `true`, script will run itself every `X` seconds. You need handle crashes and restarts by yourself |
| `interval`          |   `integer`   |  `60`   |  ❌ _No_   | Interval in seconds for `self_running` option.                                                               |

You can check configuration flow [here](FLOW.md) to see how the script searches for configuration.

### Loading IDs from file

If you want to load IDs from [JSON](https://www.w3schools.com/js/js_json_arrays.asp) file, that file should contain one valid array of IDs.  
In all other file types you can separate IDs by anything you want (new line, space, symbol, letter). The only requirement is that IDs should not be encrypted or encoded in any way.

IDs from `input_file` and IDs provided by other methods will **Not** be merged. Use just one method at a time.

## Usage

### With Process Manager

If you are planning to use this script for a long time for a large number of users, I would recommend running it with Process Manager (like [PM2](https://github.com/Unitech/pm2), [Servicer](https://servicer.dev/), [systemd](https://en.wikipedia.org/wiki/Systemd) and similar) that will restart it if it crashes.  
For this you also need to set `self_running` to `true` in `config.json` file.

### With Cron

If you are planning to use this script for a small number of users (maybe just for yourself), you can run it with [Cron](https://cronitor.io/guides/cron-jobs).  
[Crontab.guru](https://crontab.guru/) will help you to create a cron schedule expression.

```bash
# Run script every 5 minutes
*/5 * * * * /path/to/executable/php /path/to/sig.php
```

**Not recommended use is to hold files of this script in public web directory. If you do so, make sure you are not exposing files containing sensitive data like file with Steam API Key or files with IDs.** Remember that anyone can run this script if they know the URL. That might lead to flooding your server. There are ways to prevent this, but it's better to keep this script in a private directory and use other methods to execute it.

## Notes

Keep in mind that there is limit of `100,000` requests per day for Steam API. If you are planning to use this script for a large number of users, you should calculate how often you can run this script. Maximum number of users to check in one request is `100`. So if you have `1000` users, you will need to make `10` requests in one run. So, in this example you can perform entire operation every `≈ 8.64 seconds` to not exceed the limit. By default, script calculates minimal interval to prevent exceeding the limit.

Dealing with many users can be heavy for your server. If you are planning to use this script as part of a public service, you should consider using good hosting provider. Free service providers may ban your account if you exceed their limits.

When you run the script for the first time, it will generate statuses for all IDs. This can take a lot of time and machine resources. On next runs, it will only generate them for people whose statuses have changed.

Due to your [server configuration](<https://en.wikipedia.org/wiki/Cache_(computing)>), users browser [caching](<https://en.wikipedia.org/wiki/Cache_(computing)>), and other factors, you (and your users) may experience issues with images not refreshing as expected. This script have no control over these factors.

## TODO's

- [ ] Script is fetching data for all IDs (no matter for how many).
- [ ] Script is able to generate image.
- [ ] Workflow for generating example images.
- [x] Cleanups for IDs that are not used anymore.
- [ ] Customizations for individual IDs.
- [x] Allow to use any kind of file for IDs.
- [ ] Possibility to pass all options as arguments.
- [ ] Think about merging IDs from different sources and not break support for old PHP versions.
