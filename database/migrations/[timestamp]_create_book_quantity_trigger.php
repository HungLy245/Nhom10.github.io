<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateBookQuantityTrigger extends Migration
{
    public function up()
    {
        // Tạo bảng log để theo dõi thay đổi số lượng
        DB::unprepared('
            CREATE TABLE IF NOT EXISTS book_quantity_changes (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                book_id BIGINT UNSIGNED NOT NULL,
                old_quantity INT NOT NULL,
                new_quantity INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Tạo trigger theo dõi thay đổi số lượng
        DB::unprepared('
            CREATE TRIGGER after_book_quantity_update
            AFTER UPDATE ON books
            FOR EACH ROW
            BEGIN
                IF OLD.quantity != NEW.quantity THEN
                    INSERT INTO book_quantity_changes (book_id, old_quantity, new_quantity)
                    VALUES (NEW.id, OLD.quantity, NEW.quantity);
                END IF;
            END
        ');
    }

    public function down()
    {
        DB::unprepared('DROP TRIGGER IF EXISTS after_book_quantity_update');
        DB::unprepared('DROP TABLE IF EXISTS book_quantity_changes');
    }
} 