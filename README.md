GeonamesServer
==============

Introduction
------------

GeonamesServer is [ZF2](http://framework.zend.com/) module. It indexes [geonames data](http://www.geonames.org/) &amp; provides a search API using [Elasticsearch](http://www.elasticsearch.org/).   
[Elasticsearch](http://www.elasticsearch.org/) is a distributed, RESTful, open source search server based on [Apache Lucene](http://lucene.apache.org/).

→ [Composer package](https://packagist.org/packages/ghislainf/geonames-server)

## Simple demo

A demo is available [here](http://demogeonames.websquare.fr/geonames).


## Elasticsearch index mapping
```javascript
{
    "geonameid": "2988507",
    "country": "FR",
    "name": "Paris",
    "latitude": "48.85341",
    "longitude": "2.3488",
    "population": 2138551,
    "timezone": "Europe/Paris",
    "type": "city",
    "parents": [
        {
            "geonameid": "2968815",
            "name": "Paris",
            "country": "FR",
            "type": "ADM2"
        },
        {
            "geonameid": "3012874",
            "name": "Île-de-France",
            "country": "FR",
            "type": "ADM1"
        },
        {
            "geonameid": "3017382",
            "name": "France",
            "country": "FR",
            "type": "country"
        }
    ]
}
```

## Module installation
  1. install elasticsearch
  1. `cd my/project/directory`
  2. create a `composer.json` file with following contents:

     ```json
     {
         "require": {
             "ghislainf/geonames-server": "dev-master"
         }
     }
     ```
  3. install composer via `curl -s http://getcomposer.org/installer | php` (on windows, download
     http://getcomposer.org/installer and execute it with PHP)
  4. run `php composer.phar install`
  5. open `my/project/directory/configs/application.config.php` and add the following key to your `modules`:

     ```php
     'GeonamesServer',
     ```
  6. copy `config/geonamesserver.local.php` in `my/project/directory/config/autoload`
  7. edit `my/project/directory/config/autoload/geonamesserver.local.php`, let yourself guided by comments.
  8. import geonames data in your elasticsearch index :

     ```shell
     $ php public/index.php geonames_install
     ```

     ![Install process](http://dl.dropbox.com/u/6242254/install.jpg)

## Use API

### Search :
GET `/geonames/_search/{string_query}`   
GET `/geonames/_search/{string_query}/{page}`   
GET `/geonames/_search/{string_query}/{page}/{size}`

`{page}` and `{size}` are optional, by default `{page} = 1` and `{size} = 10`

### Get document :
GET `/geonames/_get/{geonameid}`   
GET `/geonames/_get/{geonameid},{geonameid},..`
