<?php

namespace App\Console\Commands;

use App\Services\Cognito\CognitoClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Illuminate\Console\Command;

class CognitoSetup extends Command
{
    protected $signature   = 'cognito:setup';
    protected $description = 'Cria o User Pool e App Client no Cognito local (cognito-local) e atualiza o .env.';

    public function handle(CognitoClient $cognito): int
    {
        $this->info('==> Iniciando setup do Cognito...');

        try {
            $poolId = $cognito->createUserPool('futebol-api-pool');
            $this->info("==> User Pool criado: {$poolId}");

            $clientId = $cognito->createUserPoolClient($poolId, 'futebol-api-client');
            $this->info("==> App Client criado: {$clientId}");

            $this->updateEnv([
                'COGNITO_USER_POOL_ID' => $poolId,
                'COGNITO_CLIENT_ID'    => $clientId,
            ]);

            $this->newLine();
            $this->line('╔══════════════════════════════════════════╗');
            $this->line('║  Cognito inicializado com sucesso!       ║');
            $this->line('╠══════════════════════════════════════════╣');
            $this->line("║  COGNITO_USER_POOL_ID={$poolId}");
            $this->line("║  COGNITO_CLIENT_ID={$clientId}");
            $this->line('╚══════════════════════════════════════════╝');
            $this->newLine();
            $this->info('.env atualizado automaticamente.');

            return self::SUCCESS;
        } catch (CognitoIdentityProviderException $e) {
            $this->error('Falha ao criar recursos no Cognito: ' . $e->getMessage());
            $this->error('Verifique se o container "futebol-api-cognito" está saudável.');
            return self::FAILURE;
        }
    }

    private function updateEnv(array $values): void
    {
        $envPath = base_path('.env');
        $content = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);
    }
}
