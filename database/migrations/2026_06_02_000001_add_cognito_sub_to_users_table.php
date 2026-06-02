<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identificador único do usuário no Cognito (claim 'sub' do JWT).
            // Nullable para permitir migração de usuários legados sem re-cadastro no Cognito.
            $table->string('cognito_sub')->nullable()->unique()->after('email');

            // Senha local não é mais gerenciada pela aplicação
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['cognito_sub']);
            $table->dropColumn('cognito_sub');
            $table->string('password')->nullable(false)->change();
        });
    }
};
