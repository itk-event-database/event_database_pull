# Event database pull (Drupal module)

## Installation ##

```
cd «drupal root»
composer require itk-event-database/event_database_pull dev-develop
```


## Development ##

This module depends on the [Event database client](https://github.com/itk-event-database/event-database-client).

During development you may have to make changes to the client, and to make this easier you can `git checkout` the client into a local folder and require the local client (rather than using the GitHub repository). To do this, add

```
cd «drupal root»
mkdir modules/lib
cd modules/lib
git clone --branch develop https://github.com/itk-event-database/event-database-client.git
```

```
cd «drupal root»
rm -fr vendor/itk-event-database/event-database-client
ln -sf ../../modules/lib/event-database-client vendor/itk-event-database/event-database-client
```


Edit `event_database_pull/composer.json` and change `repositories` to look like this

```
    …
    "repositories": [
        {
            "type": "path",
            "url": "modules/lib/event-database-client"
        },
        {
            "type": "vcs",
            "url": "https://github.com/itk-event-database/event-database-client"
        }
    ],
    …

```

## Further development
* Lacks support for search on events lists
* Lacks a proper code review
* A rewrite of templates to better reflect the available variables
