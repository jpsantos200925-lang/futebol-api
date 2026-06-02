#!/bin/bash
# Inicializa o Cognito User Pool no LocalStack para desenvolvimento local.
# Este script é executado automaticamente pelo LocalStack após o serviço estar pronto.
# As variáveis geradas (Pool ID e Client ID) são gravadas em /etc/localstack/cognito.env
# para que possam ser referenciadas nos logs e no .env da aplicação.

set -e

REGION="us-east-1"

echo "==> [Cognito] Criando User Pool..."

POOL_ID=$(awslocal cognito-idp create-user-pool \
    --region "$REGION" \
    --pool-name "futebol-api-pool" \
    --policies '{
        "PasswordPolicy": {
            "MinimumLength": 8,
            "RequireUppercase": false,
            "RequireLowercase": false,
            "RequireNumbers": false,
            "RequireSymbols": false
        }
    }' \
    --auto-verified-attributes email \
    --username-attributes email \
    --query 'UserPool.Id' \
    --output text)

echo "==> [Cognito] User Pool criado: $POOL_ID"

echo "==> [Cognito] Criando App Client..."

CLIENT_ID=$(awslocal cognito-idp create-user-pool-client \
    --region "$REGION" \
    --user-pool-id "$POOL_ID" \
    --client-name "futebol-api-client" \
    --no-generate-secret \
    --explicit-auth-flows \
        ALLOW_USER_PASSWORD_AUTH \
        ALLOW_REFRESH_TOKEN_AUTH \
        ALLOW_USER_SRP_AUTH \
    --query 'UserPoolClient.ClientId' \
    --output text)

echo "==> [Cognito] App Client criado: $CLIENT_ID"

# Persiste as variáveis para fácil consulta via: docker exec futebol-api-localstack cat /etc/localstack/cognito.env
mkdir -p /etc/localstack
cat > /etc/localstack/cognito.env <<EOF
COGNITO_USER_POOL_ID=$POOL_ID
COGNITO_CLIENT_ID=$CLIENT_ID
COGNITO_REGION=$REGION
COGNITO_ENDPOINT=http://localhost:4566
EOF

echo "==> [Cognito] Configuração salva em /etc/localstack/cognito.env"
echo "==> [Cognito] Inicialização concluída com sucesso!"
echo ""
echo "    COGNITO_USER_POOL_ID=$POOL_ID"
echo "    COGNITO_CLIENT_ID=$CLIENT_ID"
echo ""
echo "    Copie os valores acima para o seu .env"
