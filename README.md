### Use SQLite as Cache Backend for your TYPO3 projects

Provides a cache backend for TYPO3 that stores all cache information in SQLite.

Another side project...

## Why this package?

TYPO3 ships with a "Database Cache Backend" for storing a cached version of a page,
of the pagetree, eventually moving a lot of code in the same database as the actual
content, which is typically MySQL/MariaDB. A typical cached page still requires the
database for a handful of queries, plus dozens of checks in the "database cache".

However, when the database system is stored on a separate internal server (the "database
server"), which is the case for 99.999% of all shared hosting providers, the
network latency (= the duration to have data travel through the LAN cable) increases,
which is a natural thing. A few people then played around with using a Filesystem-based
cache instead, which also ships with TYPO3 by default, but is not used for the
typical page caches. When having a few hundred cache entries (which can happen fast),
your file system and the actual hard drives (SSD, please) need to be _really_ good.

This package tries to merge the best of both worlds:

TYPO3 has support for SQLite since TYPO3 v9. SQLite can be used to
be the database, but stores all information in one single file, which ideally is
on the same server as the project. Despite it's name "Lite", it is a fully featured
RDBMS which perfectly fits our needs, and it's very fast.

The main idea behind this package here is providing a SQLite cache backend for TYPO3,
which automatically takes care of creating the database schema and the SQLite file.
If the file is removed (through deployment, or by accident), the schema
is re-created.

While at b13.com, we use a memory-based cache backend such as Redis exclusively in our
projects, I built this small package to demonstrate that even small TYPO3 projects
can benefit from an optimized cache backend. Maybe this package even finds its way
into TYPO3 if the maintainers think it's worthy.

## Installation

You need TYPO3 v9.3 or later to use this functionality. Also, ensure to have
the "php-sqlite" package activated, and that's it.

Use composer to install this package `composer req bmack/local-caches`.

## Configuration

The SQLite database file

After the installation, edit your AdditionalConfiguration.php to define
the use of SQLite cache backend. Here's an example for TYPO3 v10+:

    $caches = [
        'hash',
        'pages',
        'pagesection',
        'rootline',
        'extbase',
    ];

    foreach ($caches as $cacheName) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$cacheName]['backend'] = \Bmack\LocalCaches\SqliteCacheBackend::class;
    }


## Side Notes

* I've been using this package in a few smaller sites and it "kinda works"
* It creates the DB file and DB structure if it does not exist, maybe there is a better way to do things
* I used a similar approach for b13.com in production for a bit and through profiling I found that a fully cached and with PHP profiling I quickly found page was faster then with Redis. I might need to give this another try and show some stats.

## License

The package is licensed under GPL v2+, same as the TYPO3 Core. For details see the LICENSE file in this repository.

## Open Issues

If you find an issue, feel free to create an issue on GitHub or a pull request.

## Credits

This package was created by [Benni Mack](https://github.com/bmack) in 2023 for [b13 GmbH](https://b13.com).

Inspiration came from Andy Grunwald and Wolfgang Gassler from [Engineering Kiosk Podcast](https://engineeringkiosk.dev/).

[Find more TYPO3 extensions we have developed](https://b13.com/useful-typo3-extensions-from-b13-to-you) that help us deliver value in client projects. As part of the way we work, we focus on testing and best practices to ensure long-term performance, reliability, and results in all our code.
