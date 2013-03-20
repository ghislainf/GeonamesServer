GeonamesServer
==============

Introduction
------------

GeonamesServer is [ZF2](http://framework.zend.com/) module. It indexes [geonames data](http://www.geonames.org/) &amp; provides a search API using [Elasticsearch](http://www.elasticsearch.org/).   
[Elasticsearch](http://www.elasticsearch.org/) is a distributed, RESTful, open source search server based on [Apache Lucene](http://lucene.apache.org/).

### Elasticsearch index mapping
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

Module installation
------------

 * Add module in your ZF2 project
 * Install Elasticsearch
 * Edit elasticsearch and installer config in `config/module.config.php`, let yourself guided by comments
 * Run install :

```shell
$ php public/index.php geonames_install
```

![Install process](http://dl.dropbox.com/u/6242254/install.jpg)

## Use API

### Search :
GET `/geonames/_search/{string_query}`   
GET `/geonames/_search/{string_query}/{page}`   
GET `/geonames/_search/{string_query}/{page}/{size}`

`{page}` and `{size}` are optionnel, by default `{page} = 1` and `{size} = 10`

### Get document :
`/geonames/_get/{geonameid}`   
`/geonames/_get/{geonameid},{geonameid},..`
