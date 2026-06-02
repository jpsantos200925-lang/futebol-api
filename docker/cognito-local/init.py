"""
Script de inicialização do Cognito local.
Cria o User Pool e o App Client no cognito-local e grava os IDs
em /output/cognito.env para consulta posterior.
"""

import json
import os
import sys
import time
import urllib.error
import urllib.request

BASE_URL = "http://futebol-api-cognito:9229"
OUTPUT_FILE = "/output/cognito.env"


def cognito_request(target: str, data: dict) -> dict:
    body = json.dumps(data).encode("utf-8")
    req = urllib.request.Request(
        BASE_URL,
        data=body,
        headers={
            "X-Amz-Target": f"AmazonCognitoIdentityProvider.{target}",
            "Content-Type": "application/x-amz-json-1.1",
        },
        method="POST",
    )
    with urllib.request.urlopen(req) as response:
        return json.loads(response.read())


def wait_for_cognito(max_retries: int = 30, delay: float = 2.0) -> None:
    print(f"==> Aguardando cognito-local em {BASE_URL} ...")
    for attempt in range(max_retries):
        try:
            urllib.request.urlopen(BASE_URL, timeout=3)
        except urllib.error.HTTPError:
            # HTTPError significa que o serviço está respondendo (apenas recusa a request vazia)
            print(f"==> cognito-local está pronto (tentativa {attempt + 1})")
            return
        except Exception:
            pass
        time.sleep(delay)
    print("ERRO: cognito-local não ficou disponível a tempo.")
    sys.exit(1)


def main() -> None:
    # Se já foi inicializado, apenas exibe o conteúdo existente
    if os.path.exists(OUTPUT_FILE):
        with open(OUTPUT_FILE) as f:
            content = f.read()
        print("==> Já inicializado. Conteúdo atual de cognito.env:")
        print(content)
        return

    wait_for_cognito()

    print("==> Criando User Pool...")
    pool_data = cognito_request(
        "CreateUserPool",
        {
            "PoolName": "futebol-api-pool",
            "UsernameAttributes": ["email"],
            "Policies": {
                "PasswordPolicy": {
                    "MinimumLength": 8,
                    "RequireUppercase": False,
                    "RequireLowercase": False,
                    "RequireNumbers": False,
                    "RequireSymbols": False,
                }
            },
        },
    )
    pool_id = pool_data["UserPool"]["Id"]
    print(f"==> Pool ID: {pool_id}")

    print("==> Criando App Client...")
    client_data = cognito_request(
        "CreateUserPoolClient",
        {
            "UserPoolId": pool_id,
            "ClientName": "futebol-api-client",
            "ExplicitAuthFlows": [
                "ALLOW_USER_PASSWORD_AUTH",
                "ALLOW_REFRESH_TOKEN_AUTH",
                "ALLOW_USER_SRP_AUTH",
            ],
        },
    )
    client_id = client_data["UserPoolClient"]["ClientId"]
    print(f"==> Client ID: {client_id}")

    env_content = (
        f"COGNITO_USER_POOL_ID={pool_id}\n"
        f"COGNITO_CLIENT_ID={client_id}\n"
        f"COGNITO_REGION=us-east-1\n"
        f"COGNITO_ENDPOINT=http://futebol-api-cognito:9229\n"
    )

    with open(OUTPUT_FILE, "w") as f:
        f.write(env_content)

    print("==> Inicialização concluída! Copie os valores abaixo para o seu .env:")
    print(env_content)


if __name__ == "__main__":
    main()
