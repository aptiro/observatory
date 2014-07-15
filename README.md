APTI-obs
====

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
    enabled_extensions:
      - AptiScraper

    debug: false # for production
    scraping_key: asdf
```


periodically run the scraper:
```
    curl 'http://apti-obs.devel/apti-scraper/' -d key=asdf
```
