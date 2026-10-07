<?php

namespace Renatio\DynamicPDF\Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Mockery;
use October\Rain\Database\Model;
use October\Rain\Database\Pivot;
use October\Tests\Concerns\InteractsWithAuthentication;
use October\Tests\Concerns\PerformsMigrations;
use October\Tests\Concerns\PerformsRegistrations;
use PDO;
use ReflectionClass;
use Renatio\DynamicPDF\Classes\PDFManager;
use Renatio\DynamicPDF\Models\Template;
use System\Classes\SiteManager;
use System\Models\Parameter;
use TestCase;
use Throwable;

abstract class OctoberPestTestCase extends TestCase
{
    use InteractsWithAuthentication;
    use PerformsMigrations;
    use PerformsRegistrations;

    /** @var array<class-string>|null */
    protected static ?array $cachedModelClasses = null;

    protected static bool $databaseMigrated = false;

    protected static ?PDO $inMemoryConnection = null;

    public function setUpOctoberPlugin(): void
    {
        $this->loadAllPlugins();

        if (self::$inMemoryConnection !== null) {
            DB::connection()->setPdo(self::$inMemoryConnection);
        }

        if (! self::$databaseMigrated) {
            $this->migrateDatabase();

            self::$databaseMigrated = true;

            if ($this->usingInMemoryDatabase()) {
                self::$inMemoryConnection = DB::connection()->getPdo();
            }
        }

        $this->forgetDatabaseCheck();
        $this->resetStaticState();
        $this->beginDatabaseTransaction();

        Model::unguard();

        Mail::pretend();
    }

    public function tearDownOctoberPlugin(): void
    {
        $error = null;

        $steps = [
            function (): void {
                if (class_exists(Mockery::class)) {
                    Mockery::close();
                }
            },
            $this->rollbackDatabaseTransaction(...),
            $this->flushModelEventListeners(...),
            $this->resetStaticState(...),
        ];

        foreach ($steps as $step) {
            try {
                $step();
            } catch (Throwable $e) {
                $error ??= $e;
            }
        }

        if ($error !== null) {
            throw $error;
        }
    }

    protected function resetStaticState(): void
    {
        Template::flushLayoutCache();
        PDFManager::forgetInstance();
        Parameter::clearInternalCache();
        SiteManager::instance()->resetCache();
    }

    /**
     * System::hasDatabase() is memoised, and a check made before the migrated PDO
     * was attached would make Parameter::get() skip the table for the whole test.
     */
    protected function forgetDatabaseCheck(): void
    {
        $helper = app('system.helper');

        if (property_exists($helper, 'hasDatabaseCache')) {
            self::setProtectedProperty($helper, 'hasDatabaseCache', null);
        }
    }

    protected function usingInMemoryDatabase(): bool
    {
        return config('database.connections.' . config('database.default') . '.database') === ':memory:';
    }

    protected function beginDatabaseTransaction(): void
    {
        $connection = DB::connection();

        $dispatcher = $connection->getEventDispatcher();
        $connection->unsetEventDispatcher();

        if ($connection->transactionLevel() > 0) {
            $connection->rollBack(0);
        }

        /**
         * A transaction orphaned by an earlier test's application is invisible to this
         * connection's counter, so only the shared PDO can roll it back.
         */
        if ($connection->getPdo()->inTransaction()) {
            $connection->getPdo()->rollBack();
        }

        $connection->beginTransaction();
        $connection->setEventDispatcher($dispatcher);
    }

    protected function rollbackDatabaseTransaction(): void
    {
        $connection = DB::connection();

        $dispatcher = $connection->getEventDispatcher();
        $connection->unsetEventDispatcher();

        try {
            if ($connection->getPdo()?->inTransaction()) {
                $connection->rollBack();
            }
        } catch (Throwable) {
            self::$databaseMigrated = false;
            self::$inMemoryConnection = null;
        }

        $connection->setEventDispatcher($dispatcher);
    }

    protected function flushModelEventListeners(): void
    {
        self::$cachedModelClasses ??= $this->discoverModelClasses();

        foreach (self::$cachedModelClasses as $class) {
            try {
                $class::flushEventListeners();
            } catch (Throwable) {
                continue;
            }
        }

        Model::flushEventListeners();
    }

    /**
     * @return array<class-string>
     */
    protected function discoverModelClasses(): array
    {
        $modelClasses = [];

        foreach (get_declared_classes() as $class) {
            if ($class === Pivot::class || str_starts_with($class, 'Mockery')) {
                continue;
            }

            try {
                $reflectClass = new ReflectionClass($class);

                if (
                    ! $reflectClass->isInstantiable() ||
                    ! $reflectClass->isSubclassOf(Model::class) ||
                    $reflectClass->isSubclassOf(Pivot::class)
                ) {
                    continue;
                }

                if (method_exists($class, 'flushEventListeners')) {
                    $modelClasses[] = $class;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $modelClasses;
    }

    protected function guessPluginCodeFromTest(): string
    {
        return 'Renatio.DynamicPDF';
    }
}
