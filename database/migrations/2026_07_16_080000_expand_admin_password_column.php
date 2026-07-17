<?php
use Illuminate\Support\Facades\DB; use Illuminate\Database\Migrations\Migration;
class ExpandAdminPasswordColumn extends Migration { public function up(){DB::statement('ALTER TABLE tbl_admin MODIFY admin_password VARCHAR(255) NOT NULL');} public function down(){DB::statement('ALTER TABLE tbl_admin MODIFY admin_password VARCHAR(32) NOT NULL');} }
