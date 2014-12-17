## APTI-obs

install composer dependencies:
```
    curl -s http://getcomposer.org/installer | php
    php composer.phar install
```


update database schema:
```
    php app/nut database:update
```


make changes in `app/config/config.yml`:
```yaml
    sitename: ApTI Observatory
    theme: default
    timezone: EET

    enabled_extensions:
      - AptiScraper
      - AptiMainMenu
      - AptiContent
      - AptiTagCloud
      - AptiSuggest

    debug: false # for production
    scraping_key: asdf

    apti_mail:
      key: "MANDRILL-KEY-HERE"
      recipients:
        - email: "foo@example.com"
        - email: "bar@example.com"
```


periodically run the scraper:
```
    curl 'http://apti-obs.devel/apti-scraper/' -d key=asdf
```

### License
apti-obs is based on the Bolt CMS, which is licensed under
the MIT license. Original source code is copyright 2014,
Victor Avasiloaei and Alex Morega, licensed under the MIT
license.
