# Personal Steam Signature Generator

This [PHP](https://www.php.net/) script generates [PNG](https://en.wikipedia.org/wiki/PNG) image with status of user on Steam.
This is personal script for just one user.  
Use [CRON](https://cronitor.io/guides) to execute this script.

# TODO's

- [ ] Fix visible errors
- [ ] Fix user status logic (status is not always correct)
- [ ] Possibility to pass data in URL (use [parse_ur](https://www.php.net/manual/en/function.parse-url.phpl) for that)
- [ ] Read sensitive data (like Steam API key) from file that is not accessible from web (in case when they were not passed in URL)
