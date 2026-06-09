cls<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check if we're using SQLite (for testing compatibility).
     */
    private function isSqlite(): bool
    {
        return DB::connection()->getDriverName() === 'sqlite';
    }

    public function up(): void
    {
        // SQLite doesn't support DROP FOREIGN KEY syntax
        // Skip FK drops if we're using SQLite (it doesn't have FK by default)
        if (!$this->isSqlite()) {
            // 1. Drop FK lama
            DB::statement('ALTER TABLE activation_tokens DROP FOREIGN KEY fk_activation_tokens_user');
            // 2. Drop index (key) lama yang terkait FK
            DB::statement('ALTER TABLE activation_tokens DROP INDEX fk_activation_tokens_user');
        }
        
        // 3. Ubah user_id menjadi nullable
        if ($this->isSqlite()) {
            // SQLite: drop and recreate column
            Schema::table('activation_tokens', function ($table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        } else {
            DB::statement('ALTER TABLE activation_tokens MODIFY user_id bigint unsigned NULL');
        }
        
        // 4. Tambah FK baru dengan ON DELETE SET NULL (MySQL only)
        if (!$this->isSqlite()) {
            DB::statement('ALTER TABLE activation_tokens ADD CONSTRAINT fk_activation_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
        }
        
        // 5. Tambah kolom customer_account_request_id (check if not exists)
        if ($this->isSqlite()) {
            if (!Schema::hasColumn('activation_tokens', 'customer_account_request_id')) {
                Schema::table('activation_tokens', function ($table) {
                    $table->unsignedBigInteger('customer_account_request_id')->nullable()->after('user_id');
                });
            }
        } else {
            DB::statement('ALTER TABLE activation_tokens ADD customer_account_request_id bigint unsigned NULL AFTER user_id');
        }
        
        // 6. Tambah index untuk FK baru (check if not exists)
        if (!$this->isSqlite() || !Schema::hasColumn('activation_tokens', 'customer_account_request_id')) {
            // Only add index if we actually added the column
            if ($this->isSqlite()) {
                // For SQLite, add index separately if column exists
                DB::statement('CREATE INDEX IF NOT EXISTS fk_activation_tokens_request ON activation_tokens (customer_account_request_id)');
            } else {
                DB::statement('ALTER TABLE activation_tokens ADD INDEX fk_activation_tokens_request (customer_account_request_id)');
            }
        }
        
        // 7. Tambah FK ke customer_account_requests (MySQL only)
        if (!$this->isSqlite()) {
            DB::statement('ALTER TABLE activation_tokens ADD CONSTRAINT fk_activation_tokens_request FOREIGN KEY (customer_account_request_id) REFERENCES customer_account_requests(id) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        // 1. Drop FK & index customer_account_request_id
        if (!$this->isSqlite()) {
            DB::statement('ALTER TABLE activation_tokens DROP FOREIGN KEY fk_activation_tokens_request');
        }
        DB::statement('ALTER TABLE activation_tokens DROP INDEX fk_activation_tokens_request');
        
        // 2. Drop kolom customer_account_request_id
        Schema::table('activation_tokens', function ($table) {
            $table->dropColumn('customer_account_request_id');
        });
        
        // 3. Drop FK lama user_id
        if (!$this->isSqlite()) {
            DB::statement('ALTER TABLE activation_tokens DROP FOREIGN KEY fk_activation_tokens_user');
            DB::statement('ALTER TABLE activation_tokens DROP INDEX fk_activation_tokens_user');
        }
        
        // 4. Kembalikan user_id ke NOT NULL
        if ($this->isSqlite()) {
            Schema::table('activation_tokens', function ($table) {
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            });
        } else {
            DB::statement('ALTER TABLE activation_tokens MODIFY user_id bigint unsigned NOT NULL');
        }
        
        // 5. Tambah FK lama dengan ON DELETE CASCADE (MySQL only)
        if (!$this->isSqlite()) {
            DB::statement('ALTER TABLE activation_tokens ADD CONSTRAINT fk_activation_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
            DB::statement('ALTER TABLE activation_tokens ADD INDEX fk_activation_tokens_user (user_id)');
        }
    }
};