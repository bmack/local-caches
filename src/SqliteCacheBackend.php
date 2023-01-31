<?php

declare(strict_types=1);

namespace Bmack\LocalCaches;

use Doctrine\DBAL\Exception\TableNotFoundException;
use TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SqliteCacheBackend extends Typo3DatabaseBackend implements TaggableBackendInterface
{
    /**
     * @var string
     */
    protected $cacheDbFile;

    /**
     * @var string
     */
    protected $connectionName = 'localcaches';

    /**
     * Set cache frontend instance and calculate data and tags table name
     *
     * @param FrontendInterface $cache The frontend for this backend
     */
    public function setCache(FrontendInterface $cache)
    {
        parent::setCache($cache);
        $this->cacheDbFile = Environment::getVarPath() . '/' . $this->connectionName . '.sqlite';
        $this->setUpDatabaseConnection();
    }

    protected function setUpDatabaseConnection(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][$this->connectionName] = [
            'driver' => 'pdo_sqlite',
            'path' => $this->cacheDbFile,
        ];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$this->cacheTable] = $this->connectionName;
        $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][$this->tagsTable] = $this->connectionName;
        if (!file_exists($this->cacheDbFile)) {
            $this->ensureCacheTablesExist();
        }
    }

    /**
     * Saves data in a cache file.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws Exception if no cache frontend has been set.
     * @throws InvalidDataException if the data to be stored is not a string.
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        try {
            parent::set($entryIdentifier, $data, $tags, $lifetime);
        } catch (TableNotFoundException $e) {
            $this->ensureCacheTablesExist();
            parent::set($entryIdentifier, $data, $tags, $lifetime);
        }
    }

    /**
     * Loads data from a cache file.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's data as a string or FALSE if the cache entry could not be loaded
     */
    public function get($entryIdentifier)
    {
        try {
            return parent::get($entryIdentifier);
        } catch (TableNotFoundException $e) {
            $this->ensureCacheTablesExist();
            return parent::get($entryIdentifier);
        }
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier Specifies the identifier to check for existence
     * @return bool TRUE if such an entry exists, FALSE if not
     */
    public function has($entryIdentifier)
    {
        try {
            return parent::has($entryIdentifier);
        } catch (TableNotFoundException $e) {
            $this->ensureCacheTablesExist();
            return parent::has($entryIdentifier);
        }
    }

    /**
      * Removes all cache entries matching the specified identifier.
      * Usually this only affects one entry.
      *
      * @param string $entryIdentifier Specifies the cache entry to remove
      * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
      */
    public function remove($entryIdentifier)
    {
        try {
            return parent::remove($entryIdentifier);
        } catch (TableNotFoundException $e) {
            $this->ensureCacheTablesExist();
            return parent::remove($entryIdentifier);
        }
    }

    /**
     * Finds and returns all cache entries which are tagged by the specified tag.
     *
     * @param string $tag The tag to search for
     * @return array An array with identifiers of all matching entries. An empty array if no entries matched
     */
    public function findIdentifiersByTag($tag)
    {
        try {
            return parent::findIdentifiersByTag($tag);
        } catch (TableNotFoundException $e) {
            $this->ensureCacheTablesExist();
            return parent::findIdentifiersByTag($tag);
        }
    }

    /**
     * Removes all cache entries of this cache.
     */
    public function flush()
    {
        try {
            parent::flush();
        } catch (TableNotFoundException $e) {
            // do nothing
        }
    }

    /**
     * Removes all entries tagged by any of the specified tags. Performs the SQL
     * operation as a bulk query for better performance.
     *
     * @param string[] $tags
     */
    public function flushByTags(array $tags)
    {
        try {
            parent::flushByTags($tags);
        } catch (TableNotFoundException $e) {
            $this->ensureCacheTablesExist();
            parent::flushByTags($tags);
        }
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     */
    public function flushByTag($tag)
    {
        try {
            parent::flushByTag($tag);
        } catch (TableNotFoundException $e) {
            $this->ensureCacheTablesExist();
            parent::flushByTag($tag);
        }
    }

    /**
     * Does garbage collection
     */
    public function collectGarbage()
    {
        try {
            parent::collectGarbage();
        } catch (TableNotFoundException $e) {
            $this->ensureCacheTablesExist();
            parent::collectGarbage();
        }
    }

    /**
     * This database backend uses some optimized queries for mysql
     * to get maximum performance.
     */
    protected function isConnectionMysql(Connection $connection): bool
    {
        return false;
    }

    protected function ensureCacheTablesExist(): void
    {
        $sqlCode = $this->getTableDefinitions();
        $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
        $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
        $createTableStatements = $sqlReader->getCreateTableStatementArray($sqlCode);
        $schemaMigrationService->install($createTableStatements);
    }
}
