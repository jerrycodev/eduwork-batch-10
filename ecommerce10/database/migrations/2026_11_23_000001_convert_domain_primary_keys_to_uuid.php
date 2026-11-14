<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<string, list<array{0: string, 1: string, 2: bool}>> */
    private array $foreignKeys = [
        'products' => [['category_id', 'product_categories', false]],
        'carts' => [['user_id', 'users', false], ['product_id', 'products', false]],
        'orders' => [['user_id', 'users', false], ['cart_id', 'carts', false]],
        'order_items' => [['order_id', 'orders', false], ['product_id', 'products', false]],
        'user_addresses' => [['user_id', 'users', true]],
        'product_reviews' => [['product_id', 'products', true], ['user_id', 'users', true]],
        'product_review_media' => [['product_review_id', 'product_reviews', true]],
        'order_return_requests' => [['order_id', 'orders', true], ['user_id', 'users', true]],
        'order_cancellation_requests' => [['order_id', 'orders', true], ['user_id', 'users', true]],
        'stock_movements' => [['product_id', 'products', true], ['created_by', 'users', true]],
        'wishlist_items' => [['user_id', 'users', true], ['product_id', 'products', true]],
    ];

    /** @var list<string> */
    private array $tables = [
        'users', 'product_categories', 'products', 'carts', 'orders', 'order_items',
        'user_addresses', 'product_reviews', 'product_review_media', 'order_return_requests',
        'order_cancellation_requests', 'stock_movements', 'wishlist_items',
    ];

    public function up(): void
    {
        // Fresh databases already use UUIDs from the updated create-table migrations.
        if (DB::getDriverName() !== 'mysql') {
            // SQLite test databases are created from the updated UUID migrations above.
            return;
        }

        $idType = DB::table('information_schema.COLUMNS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'users')
            ->where('COLUMN_NAME', 'id')
            ->value('DATA_TYPE');

        if (in_array($idType, ['char', 'varchar'], true)) {
            return;
        }

        // Keep sessions attached to their users even though sessions remains an infrastructure table.
        Schema::table('sessions', function (Blueprint $table): void {
            $table->char('user_id_uuid', 36)->nullable();
        });

        // Generate a UUID v4 for every existing domain record and retain old->new maps in memory.
        $maps = [];

        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->char('id_uuid', 36)->nullable();
            });

            $maps[$tableName] = [];
            DB::table($tableName)->select('id')->orderBy('id')->chunk(500, function ($rows) use ($tableName, &$maps): void {
                foreach ($rows as $row) {
                    $uuid = (string) Str::uuid();
                    $maps[$tableName][(string) $row->id] = $uuid;
                    DB::table($tableName)->where('id', $row->id)->update(['id_uuid' => $uuid]);
                }
            });
        }

        // Prepare all replacement FK columns before touching existing constraints.
        foreach ($this->foreignKeys as $tableName => $relations) {
            foreach ($relations as [$column, $parentTable]) {
                Schema::table($tableName, function (Blueprint $table) use ($column): void {
                    $table->char($column.'_uuid', 36)->nullable();
                });

                DB::table($tableName)->select(['id', $column])->orderBy('id')->chunk(500, function ($rows) use ($tableName, $column, $parentTable, $maps): void {
                    foreach ($rows as $row) {
                        $oldForeignId = $row->{$column};
                        $newForeignId = $oldForeignId === null ? null : ($maps[$parentTable][(string) $oldForeignId] ?? null);

                        if ($oldForeignId !== null && $newForeignId === null) {
                            throw new RuntimeException("Unable to map {$tableName}.{$column} value {$oldForeignId} to {$parentTable}.");
                        }

                        DB::table($tableName)->where('id', $row->id)->update([$column.'_uuid' => $newForeignId]);
                    }
                });
            }
        }

        // Prepare the polymorphic reference column separately because it has no declared FK.
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->char('reference_id_uuid', 36)->nullable();
        });

        // Convert polymorphic stock movement references where their model is a domain model.
        DB::table('stock_movements')->select(['id', 'reference_type', 'reference_id'])->whereNotNull('reference_id')->orderBy('id')->chunk(500, function ($rows) use ($maps): void {
            foreach ($rows as $row) {
                $type = strtolower((string) $row->reference_type);
                $parent = str_contains($type, 'order') ? 'orders' : (str_contains($type, 'product') ? 'products' : null);
                if ($parent && isset($maps[$parent][(string) $row->reference_id])) {
                    DB::table('stock_movements')->where('id', $row->id)->update(['reference_id_uuid' => $maps[$parent][(string) $row->reference_id]]);
                }
            }
        });

        DB::table('sessions')->select(['id', 'user_id'])->whereNotNull('user_id')->orderBy('id')->chunk(500, function ($rows) use ($maps): void {
            foreach ($rows as $row) {
                $uuid = $maps['users'][(string) $row->user_id] ?? null;
                DB::table('sessions')->where('id', $row->id)->update(['user_id_uuid' => $uuid]);
            }
        });

        // Capture nullability before replacing the original foreign-key columns.
        $nullableColumns = [];
        foreach ($this->foreignKeys as $tableName => $relations) {
            foreach ($relations as [$column]) {
                $nullableColumns[$tableName.'.'.$column] = DB::table('information_schema.COLUMNS')
                    ->where('TABLE_SCHEMA', DB::getDatabaseName())
                    ->where('TABLE_NAME', $tableName)
                    ->where('COLUMN_NAME', $column)
                    ->value('IS_NULLABLE') === 'YES';
            }
        }

        // Remove all foreign keys on domain tables before replacing their referenced columns.
        $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select(['TABLE_NAME', 'CONSTRAINT_NAME'])
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->whereIn('TABLE_NAME', $this->tables)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->distinct()
            ->get();

        foreach ($constraints as $constraint) {
            DB::statement(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $constraint->TABLE_NAME, $constraint->CONSTRAINT_NAME));
        }

        // Remove unique indexes that include foreign keys; they are recreated below.
        DB::statement('ALTER TABLE `product_reviews` DROP INDEX `product_reviews_product_id_user_id_unique`');
        DB::statement('ALTER TABLE `order_return_requests` DROP INDEX `order_return_requests_order_id_unique`');
        DB::statement('ALTER TABLE `order_cancellation_requests` DROP INDEX `order_cancellation_requests_order_id_unique`');
        DB::statement('ALTER TABLE `wishlist_items` DROP INDEX `wishlist_items_user_id_product_id_unique`');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->tables as $tableName) {
            DB::statement(sprintf(
                'ALTER TABLE `%1$s` DROP PRIMARY KEY, DROP COLUMN `id`, CHANGE COLUMN `id_uuid` `id` CHAR(36) NOT NULL, ADD PRIMARY KEY (`id`)',
                $tableName,
            ));
        }

        foreach ($this->foreignKeys as $tableName => $relations) {
            foreach ($relations as [$column]) {
                $isNullable = $nullableColumns[$tableName.'.'.$column];
                DB::statement(sprintf(
                    'ALTER TABLE `%1$s` DROP COLUMN `%2$s`, CHANGE COLUMN `%2$s_uuid` `%2$s` CHAR(36) %3$s',
                    $tableName,
                    $column,
                    $isNullable ? 'NULL' : 'NOT NULL',
                ));
            }
        }

        DB::statement('ALTER TABLE `stock_movements` DROP COLUMN `reference_id`, CHANGE COLUMN `reference_id_uuid` `reference_id` CHAR(36) NULL');
        DB::statement('ALTER TABLE `sessions` DROP COLUMN `user_id`, CHANGE COLUMN `user_id_uuid` `user_id` CHAR(36) NULL');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Recreate the application relationships and uniqueness constraints.
        foreach ($this->foreignKeys as $tableName => $relations) {
            foreach ($relations as [$column, $parentTable, $cascade]) {
                $delete = $tableName === 'stock_movements' ? 'SET NULL' : ($cascade ? 'CASCADE' : 'RESTRICT');
                DB::statement(sprintf(
                    'ALTER TABLE `%1$s` ADD CONSTRAINT `%1$s_%2$s_foreign` FOREIGN KEY (`%2$s`) REFERENCES `%3$s` (`id`) ON DELETE %4$s',
                    $tableName,
                    $column,
                    $parentTable,
                    $delete,
                ));
            }
        }

        DB::statement('ALTER TABLE `product_reviews` ADD UNIQUE KEY `product_reviews_product_id_user_id_unique` (`product_id`, `user_id`)');
        DB::statement('ALTER TABLE `order_return_requests` ADD UNIQUE KEY `order_return_requests_order_id_unique` (`order_id`)');
        DB::statement('ALTER TABLE `order_cancellation_requests` ADD UNIQUE KEY `order_cancellation_requests_order_id_unique` (`order_id`)');
        DB::statement('ALTER TABLE `wishlist_items` ADD UNIQUE KEY `wishlist_items_user_id_product_id_unique` (`user_id`, `product_id`)');
        DB::statement('ALTER TABLE `sessions` ADD INDEX `sessions_user_id_index` (`user_id`)');
    }

    public function down(): void
    {
        // UUIDs cannot be losslessly converted back to the previous auto-increment identifiers.
    }
};
