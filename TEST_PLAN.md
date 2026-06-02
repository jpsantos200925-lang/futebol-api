# Plano de Testes TDD — Futebol API

> Stack: Laravel 10 · PHPUnit 10 · Mockery · SQLite in-memory  
> Convenção: snake_case nos métodos de teste, prefixo `test_`  
> Cobertura-alvo: 100% das linhas de lógica de negócio

---

## Sumário

1. [Configuração Inicial](#1-configuração-inicial)
2. [Unit Tests — Services](#2-unit-tests--services)
3. [Unit Tests — Repositories](#3-unit-tests--repositories)
4. [Unit Tests — Models & Relations](#4-unit-tests--models--relations)
5. [Unit Tests — Guards & Auth](#5-unit-tests--guards--auth)
6. [Unit Tests — Observers](#6-unit-tests--observers)
7. [Unit Tests — Events & Listeners](#7-unit-tests--events--listeners)
8. [Feature Tests — Validações (Form Requests)](#8-feature-tests--validações-form-requests)
9. [Feature Tests — Endpoints HTTP](#9-feature-tests--endpoints-http)
10. [Feature Tests — Fluxos de Negócio Integrados](#10-feature-tests--fluxos-de-negócio-integrados)
11. [Resumo de Arquivos a Criar](#11-resumo-de-arquivos-a-criar)

---

## 1. Configuração Inicial

### Ajustes necessários no ambiente de testes

```xml
<!-- phpunit.xml — adicionar -->
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="COGNITO_ENDPOINT" value="mock"/>
```

### TestCase base customizado

**Arquivo:** `tests/TestCase.php`

```php
// Traits a incluir:
use RefreshDatabase;  // recria migrations a cada teste

// Helper para autenticar usuario mockando o CognitoGuard
protected function actingAsAuthUser(): self

// Helper para criar User com cognito_sub fake
protected function createAuthenticatedUser(): User
```

### Estratégia para Cognito

O `CognitoClient` deve ser injetado via DI. Nos testes, fazer mock da interface para evitar chamadas reais ao LocalStack.

---

## 2. Unit Tests — Services

### 2.1 Auth — `LoginService`

**Arquivo:** `tests/Unit/Services/Auth/LoginServiceTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_retorna_tokens_para_credenciais_validas` | Chama `CognitoClient::authenticateUser()`, retorna array com AccessToken, IdToken, RefreshToken, ExpiresIn, TokenType e status 200 |
| 2 | `test_retorna_erro_401_para_credenciais_invalidas` | Cognito lança `NotAuthorizedException`; service retorna mensagem de erro e status 401 |
| 3 | `test_retorna_erro_404_para_usuario_inexistente` | Cognito lança `UserNotFoundException`; service retorna status 404 |
| 4 | `test_retorna_erro_403_para_usuario_nao_confirmado` | Cognito lança `UserNotConfirmedException`; service retorna status 403 |

**Dependências mockadas:** `CognitoClient`

---

### 2.2 Auth — `RegisterService`

**Arquivo:** `tests/Unit/Services/Auth/RegisterServiceTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_cria_usuario_no_cognito_e_no_banco_localmente` | Chama `CognitoClient::createUser()`, salva User com cognito_sub retornado; retorna User com id/name/email e status 201 |
| 2 | `test_faz_rollback_se_cognito_falhar` | Se `createUser()` lança exception, a transação não persiste User no banco |
| 3 | `test_retorna_erro_409_para_email_duplicado_no_cognito` | `UsernameExistsException` → retorna status 409 |
| 4 | `test_retorna_erro_422_para_senha_invalida_no_cognito` | `InvalidPasswordException` → retorna status 422 |

**Dependências mockadas:** `CognitoClient`, `UserRepository`

---

### 2.3 Auth — `RefreshTokenService`

**Arquivo:** `tests/Unit/Services/Auth/RefreshTokenServiceTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_retorna_novos_tokens_com_refresh_token_valido` | Chama `CognitoClient::refreshToken()`, retorna AccessToken, IdToken, ExpiresIn, TokenType e status 200 |
| 2 | `test_retorna_erro_401_com_refresh_token_invalido` | Cognito lança exception; service retorna status 401 |

**Dependências mockadas:** `CognitoClient`

---

### 2.4 Auth — `LogoutService`

**Arquivo:** `tests/Unit/Services/Auth/LogoutServiceTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_faz_logout_com_access_token_valido` | Chama `CognitoClient::globalSignOut()`, retorna array vazio e status 200 |
| 2 | `test_retorna_erro_401_com_token_invalido` | Cognito lança exception; service retorna status 401 |

**Dependências mockadas:** `CognitoClient`

---

### 2.5 Championship — `UpdateChampionshipTableService`

**Arquivo:** `tests/Unit/Services/Championship/UpdateChampionshipTableServiceTest.php`

Este é o service mais crítico de negócio. Todos os cenários do cálculo de pontos devem ser cobertos.

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_time_visitante_vence_recebe_3_pontos` | away_goals > home_goals → away championship: +3 points, +1 victory; home: +0 points, +1 defeat |
| 2 | `test_time_mandante_vence_recebe_3_pontos` | home_goals > away_goals → home championship: +3 points, +1 victory; away: +0 points, +1 defeat |
| 3 | `test_empate_ambos_recebem_1_ponto` | away_goals == home_goals → ambos: +1 point; victories/defeats não incrementam |
| 4 | `test_gols_sao_acumulados_corretamente` | number_of_goals acumula gols marcados por cada time separadamente |
| 5 | `test_pontos_sao_acumulados_em_partidas_anteriores` | Se time já tinha pontos, soma corretamente (não substitui) |
| 6 | `test_vitórias_acumulam_corretamente_em_sequencia` | 3 vitórias consecutivas → number_of_victories == 3 |
| 7 | `test_derrotas_acumulam_corretamente_em_sequencia` | 2 derrotas → number_of_defeats == 2 |
| 8 | `test_retorna_falso_para_partida_inexistente` | match id não existe → comportamento seguro (sem exception) |

**Dependências mockadas:** `ChampionshipRepository`, `ChampionshipMatchsRepository`

---

### 2.6 Championship — Services CRUD

**Arquivo:** `tests/Unit/Services/Championship/ChampionshipServiceTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_index_retorna_todos_os_championships` | `IndexChampionshipService::execute()` chama `ChampionshipRepository::all()` |
| 2 | `test_show_retorna_championship_por_id` | `ShowChampionshipService::execute($id)` chama `ChampionshipRepository::find($id)` |
| 3 | `test_store_cria_championship_com_team_id` | `StoreChampionshipService::execute($data)` chama `ChampionshipRepository::create()` |
| 4 | `test_update_atualiza_campos_do_championship` | `UpdateChampionshipService::execute($data, $id)` chama `ChampionshipRepository::update()` |
| 5 | `test_destroy_deleta_championship_por_id` | `DestroyChampionshipService::execute($id)` chama `ChampionshipRepository::delete($id)` |

**Dependências mockadas:** `ChampionshipRepository`

---

### 2.7 ChampionshipMatch — `UpdateChampionshipMatchService`

**Arquivo:** `tests/Unit/Services/ChampionshipMatch/UpdateChampionshipMatchServiceTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_atualiza_gols_de_partida_em_andamento` | Chama `ChampionshipMatchsRepository::update()` com gols corretos |
| 2 | `test_define_end_time_automaticamente_ao_finalizar` | Quando is_ended = true, end_time é preenchido automaticamente |
| 3 | `test_dispara_evento_end_of_the_match_ao_finalizar` | Quando is_ended = true, `Event::dispatch(EndOfTheMatch)` é chamado |
| 4 | `test_nao_dispara_evento_se_is_ended_false` | is_ended = false → evento NÃO é disparado |
| 5 | `test_impede_atualizacao_de_partida_ja_finalizada` | Se is_ended == true no banco → retorna erro sem atualizar |
| 6 | `test_retorna_erro_para_partida_inexistente` | match id não existe → retorna status de erro |

**Dependências mockadas:** `ChampionshipMatchsRepository`, `Event` (fake)

---

### 2.8 ChampionshipMatch — Services CRUD

**Arquivo:** `tests/Unit/Services/ChampionshipMatch/ChampionshipMatchServiceTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_index_retorna_todas_as_partidas` | Chama `ChampionshipMatchsRepository::all()` |
| 2 | `test_show_retorna_partida_por_id` | Chama `ChampionshipMatchsRepository::find($id)` |
| 3 | `test_store_cria_partida_com_is_ended_false` | Cria partida; is_ended padrão = false |
| 4 | `test_destroy_deleta_partida_por_id` | Chama `ChampionshipMatchsRepository::delete($id)` |

---

### 2.9 Player — `StorePlayerService`

**Arquivo:** `tests/Unit/Services/Player/PlayerServiceTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_cria_jogador_com_numero_unico_no_time` | number não existe no time → cria com sucesso |
| 2 | `test_rejeita_numero_de_camisa_duplicado_no_mesmo_time` | number já existe no time → retorna erro |
| 3 | `test_permite_mesmo_numero_em_times_diferentes` | number duplicado em time diferente → permite criação |
| 4 | `test_update_atualiza_dados_do_jogador` | Chama `PlayerRepository::update()` |
| 5 | `test_destroy_deleta_jogador` | Chama `PlayerRepository::delete($id)` |
| 6 | `test_index_retorna_todos_os_jogadores` | Chama `PlayerRepository::all()` |
| 7 | `test_show_retorna_jogador_por_id` | Chama `PlayerRepository::find($id)` |

**Dependências mockadas:** `PlayerRepository`

---

### 2.10 Team — Services

**Arquivo:** `tests/Unit/Services/Team/TeamServiceTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_cria_time_com_nome_valido` | Chama `TeamRepository::create()` |
| 2 | `test_index_retorna_todos_os_times` | Chama `TeamRepository::all()` |
| 3 | `test_show_retorna_time_por_id` | Chama `TeamRepository::find($id)` |
| 4 | `test_update_atualiza_nome_do_time` | Chama `TeamRepository::update()` |
| 5 | `test_destroy_deleta_time` | Chama `TeamRepository::delete($id)` |

**Dependências mockadas:** `TeamRepository`

---

### 2.11 User — Services

**Arquivo:** `tests/Unit/Services/User/UserServiceTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_index_retorna_todos_os_usuarios` | Chama `UserRepository::all()` |
| 2 | `test_show_retorna_usuario_por_id` | Chama `UserRepository::find($id)` |
| 3 | `test_store_cria_usuario` | Chama `UserRepository::create()` |
| 4 | `test_update_atualiza_usuario` | Chama `UserRepository::update()` |
| 5 | `test_destroy_deleta_usuario` | Chama `UserRepository::delete($id)` |

---

## 3. Unit Tests — Repositories

### 3.1 `BaseRepository`

**Arquivo:** `tests/Unit/Repositories/BaseRepositoryTest.php`

Usar um repositório concreto simples (ex: `TeamRepository`) para testar os métodos base via SQLite.

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_all_retorna_collection_de_registros` | Cria 3 teams, `all()` retorna 3 |
| 2 | `test_find_retorna_registro_por_id` | Cria team, `find($id)` retorna correto |
| 3 | `test_find_retorna_null_para_id_inexistente` | `find(999)` retorna null sem exception |
| 4 | `test_create_persiste_registro` | `create(['name' => 'Flamengo'])` → existe no banco |
| 5 | `test_update_altera_campos_do_registro` | `update(['name' => 'Novo'], $id)` → banco atualizado |
| 6 | `test_delete_remove_registro` | `delete($id)` → não existe mais |
| 7 | `test_all_com_search_filtra_resultados` | Cria 'Flamengo' e 'Palmeiras'; search='Flam' → retorna só Flamengo |
| 8 | `test_all_com_limit_respeita_quantidade` | Cria 5 teams; `all(skip:0, limit:2)` → retorna 2 |
| 9 | `test_exists_retorna_true_quando_ha_registros` | Cria team → `exists()` retorna true |
| 10 | `test_exists_retorna_false_quando_vazio` | Sem teams → `exists()` retorna false |
| 11 | `test_first_retorna_primeiro_registro` | Cria 3 teams → `first()` retorna o primeiro inserido |
| 12 | `test_paginate_retorna_estrutura_de_paginacao` | `paginate(2)` retorna LengthAwarePaginator com per_page=2 |

---

### 3.2 `ChampionshipMatchsRepository`

**Arquivo:** `tests/Unit/Repositories/ChampionshipMatchsRepositoryTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_delete_by_team_id_remove_partidas_do_time_como_mandante` | Cria partidas com home_team_id; `deleteByTeamId()` remove todas |
| 2 | `test_delete_by_team_id_remove_partidas_do_time_como_visitante` | Cria partidas com away_team_id; `deleteByTeamId()` remove todas |
| 3 | `test_delete_by_team_id_nao_remove_partidas_de_outros_times` | Partidas de outro time não são afetadas |

---

## 4. Unit Tests — Models & Relations

**Arquivo:** `tests/Unit/Models/ModelRelationsTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_team_tem_muitos_players` | `Team::hasMany(Player::class)` — cria time com 3 jogadores, `$team->players` retorna 3 |
| 2 | `test_player_pertence_a_um_team` | `Player::belongsTo(Team::class)` — `$player->team` retorna o time correto |
| 3 | `test_championship_pertence_a_um_team` | `Championship::belongsTo(Team::class)` — relação correta |
| 4 | `test_championship_match_tem_away_team` | `ChampionshipMatchs::belongsTo(Team, 'away_team_id')` funciona |
| 5 | `test_championship_match_tem_home_team` | `ChampionshipMatchs::belongsTo(Team, 'home_team_id')` funciona |
| 6 | `test_player_number_cast_como_integer` | `$player->number` é int, não string |
| 7 | `test_championship_match_is_ended_cast_como_boolean` | `$match->is_ended` é bool |
| 8 | `test_championship_pontos_cast_como_integer` | `$championship->points` é int |
| 9 | `test_user_hidden_remember_token` | `$user->toArray()` não contém remember_token |
| 10 | `test_team_player_nao_tem_timestamps` | Inserção em team_to_player não exige created_at/updated_at |

---

## 5. Unit Tests — Guards & Auth

### 5.1 `CognitoGuard`

**Arquivo:** `tests/Unit/Guards/CognitoGuardTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_autentica_usuario_com_bearer_token_valido` | Header `Authorization: Bearer <token>` válido → `guard->user()` retorna User |
| 2 | `test_retorna_null_sem_header_authorization` | Sem header → `guard->user()` retorna null |
| 3 | `test_retorna_null_com_token_invalido` | `CognitoClient::verifyToken()` lança exception → null |
| 4 | `test_retorna_null_quando_usuario_nao_existe_no_banco` | Token válido mas cognito_sub não encontrado no DB → null |
| 5 | `test_verifica_que_usuario_e_autenticado_via_check` | Após user() retornar User, `guard->check()` é true |

**Dependências mockadas:** `CognitoClient`, `CognitoUserProvider`

---

### 5.2 `CognitoClient`

**Arquivo:** `tests/Unit/Services/Cognito/CognitoClientTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_verify_token_retorna_payload_para_jwt_valido` | JWT bem-formado com kid válido → decodifica e retorna claims |
| 2 | `test_verify_token_lanca_exception_para_jwt_expirado` | Token com exp no passado → exception |
| 3 | `test_verify_token_lanca_exception_para_assinatura_invalida` | JWT assinado com chave errada → exception |
| 4 | `test_jwks_e_cacheado_entre_chamadas` | `getJwks()` chamada 2x → HTTP chamado só 1x (usa cache) |

**Dependências mockadas:** `GuzzleHttp\Client`, `Illuminate\Cache`

---

## 6. Unit Tests — Observers

### 6.1 `TeamObserver`

**Arquivo:** `tests/Unit/Observers/TeamObserverTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_cria_championship_ao_criar_time` | `created(Team)` → `ChampionshipRepository::create(['team_id' => $team->id])` chamado |
| 2 | `test_deleta_partidas_ao_deletar_time` | `deleted(Team)` → `ChampionshipMatchsRepository::deleteByTeamId()` chamado |
| 3 | `test_deleta_championship_ao_deletar_time` | `deleted(Team)` → championship do time é deletado |
| 4 | `test_recria_championship_ao_restaurar_time` | `restored(Team)` → championship é recriado |
| 5 | `test_force_delete_remove_partidas_e_championship` | `forceDeleted(Team)` → mesma lógica que deleted |

**Dependências mockadas:** `ChampionshipRepository`, `ChampionshipMatchsRepository`

---

## 7. Unit Tests — Events & Listeners

### 7.1 `EndOfTheMatch` Event

**Arquivo:** `tests/Unit/Events/EndOfTheMatchTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_evento_armazena_championship_match_id` | `new EndOfTheMatch(42)->championship_match_id === 42` |

---

### 7.2 `UpdateChampionshipTable` Listener

**Arquivo:** `tests/Unit/Listeners/UpdateChampionshipTableTest.php`

| # | Método de Teste | O que valida |
|---|---|---|
| 1 | `test_listener_chama_update_championship_table_service` | `handle(new EndOfTheMatch($id))` → `UpdateChampionshipTableService::execute($id)` chamado 1x com o id correto |

**Dependências mockadas:** `UpdateChampionshipTableService`

---

## 8. Feature Tests — Validações (Form Requests)

Testar que as validações rejeitam inputs inválidos com 422 e aceitam inputs corretos.

**Arquivo:** `tests/Feature/Requests/AuthRequestsTest.php`

| # | Request | Campo | Caso inválido | Resultado esperado |
|---|---|---|---|---|
| 1 | LoginRequest | email | ausente | 422, errors.email |
| 2 | LoginRequest | email | formato inválido `"nao-email"` | 422, errors.email |
| 3 | LoginRequest | password | ausente | 422, errors.password |
| 4 | RegisterRequest | name | ausente | 422, errors.name |
| 5 | RegisterRequest | email | duplicado no banco | 422, errors.email |
| 6 | RegisterRequest | password | menos de 6 chars | 422, errors.password |
| 7 | LogoutRequest | access_token | ausente | 422, errors.access_token |
| 8 | RefreshTokenRequest | refresh_token | ausente | 422, errors.refresh_token |

**Arquivo:** `tests/Feature/Requests/TeamRequestsTest.php`

| # | Request | Campo | Caso inválido | Resultado esperado |
|---|---|---|---|---|
| 9 | StoreTeamRequest | name | ausente | 422 |
| 10 | StoreTeamRequest | name | duplicado | 422 |
| 11 | UpdateTeamRequest | name | duplicado em outro time | 422 |

**Arquivo:** `tests/Feature/Requests/PlayerRequestsTest.php`

| # | Request | Campo | Caso inválido | Resultado esperado |
|---|---|---|---|---|
| 12 | StorePlayerRequest | name | ausente | 422 |
| 13 | StorePlayerRequest | number | ausente | 422 |
| 14 | StorePlayerRequest | team_id | inexistente | 422 |
| 15 | StorePlayerRequest | number | não inteiro | 422 |

**Arquivo:** `tests/Feature/Requests/ChampionshipMatchRequestsTest.php`

| # | Request | Campo | Caso inválido | Resultado esperado |
|---|---|---|---|---|
| 16 | StoreChampionshipMatchRequest | date | formato inválido `"01/13/2024"` | 422 |
| 17 | StoreChampionshipMatchRequest | start_time | formato inválido `"25:00"` | 422 |
| 18 | StoreChampionshipMatchRequest | away_team_id | igual a home_team_id | 422 |
| 19 | StoreChampionshipMatchRequest | home_team_id | inexistente | 422 |
| 20 | UpdateChampionshipMatchRequest | away_team_goals | negativo | 422 |
| 21 | UpdateChampionshipMatchRequest | home_team_goals | não inteiro | 422 |

---

## 9. Feature Tests — Endpoints HTTP

> Estes testes usam banco SQLite in-memory com `RefreshDatabase`.  
> A autenticação é mockada via `$this->actingAs($user, 'cognito')` ou substituindo o guard no container.

---

### 9.1 Auth Endpoints

**Arquivo:** `tests/Feature/Http/AuthTest.php`

| # | Endpoint | Cenário | Status esperado | Body verificado |
|---|---|---|---|---|
| 1 | POST /api/v1/register | Dados válidos | 201 | `data.id`, `data.email`, `data.name` |
| 2 | POST /api/v1/register | Email duplicado | 422 | `errors.email` |
| 3 | POST /api/v1/register | Senha muito curta | 422 | `errors.password` |
| 4 | POST /api/v1/login | Credenciais válidas | 200 | `data.AccessToken`, `data.RefreshToken` |
| 5 | POST /api/v1/login | Senha errada | 401 | `message` presente |
| 6 | POST /api/v1/login | Usuário inexistente | 404 | `message` presente |
| 7 | POST /api/v1/logout | Token válido | 200 | `data == []` |
| 8 | POST /api/v1/refresh | Token válido | 200 | `data.AccessToken` |
| 9 | POST /api/v1/refresh | Token inválido | 401 | `message` presente |

---

### 9.2 Team Endpoints

**Arquivo:** `tests/Feature/Http/TeamTest.php`

| # | Endpoint | Cenário | Status | Verificações adicionais |
|---|---|---|---|---|
| 1 | GET /api/v1/team | Autenticado, 3 times no banco | 200 | `data` tem 3 itens |
| 2 | GET /api/v1/team | Sem auth | 401 | — |
| 3 | POST /api/v1/team | Nome único | 201 | `data.name` correto; Championship criada automaticamente |
| 4 | POST /api/v1/team | Nome duplicado | 422 | `errors.name` |
| 5 | POST /api/v1/team | Sem auth | 401 | — |
| 6 | GET /api/v1/team/{id} | ID existe | 200 | `data.id` correto |
| 7 | GET /api/v1/team/{id} | ID inexistente | 404 ou erro 4xx | — |
| 8 | PATCH /api/v1/team/{id} | Nome válido | 200 | `data.name` atualizado |
| 9 | PATCH /api/v1/team/{id} | Nome já existe em outro time | 422 | `errors.name` |
| 10 | DELETE /api/v1/team/{id} | ID existe | 200 | Time deletado do banco |
| 11 | DELETE /api/v1/team/{id} | Sem auth | 401 | — |

---

### 9.3 Player Endpoints

**Arquivo:** `tests/Feature/Http/PlayerTest.php`

| # | Endpoint | Cenário | Status | Verificações adicionais |
|---|---|---|---|---|
| 1 | GET /api/v1/player | Autenticado | 200 | Lista players |
| 2 | POST /api/v1/player | Dados válidos | 201 | `data.name`, `data.number`, `data.team_id` |
| 3 | POST /api/v1/player | Número duplicado no mesmo time | 422 | Erro de negócio |
| 4 | POST /api/v1/player | Mesmo número em time diferente | 201 | Permite criação |
| 5 | POST /api/v1/player | team_id inexistente | 422 | `errors.team_id` |
| 6 | GET /api/v1/player/{id} | ID existe | 200 | Dados corretos |
| 7 | PATCH /api/v1/player/{id} | Atualiza nome | 200 | `data.name` atualizado |
| 8 | DELETE /api/v1/player/{id} | ID existe | 200 | Deletado do banco |
| 9 | GET /api/v1/player | Sem auth | 401 | — |

---

### 9.4 Championship Endpoints

**Arquivo:** `tests/Feature/Http/ChampionshipTest.php`

| # | Endpoint | Cenário | Status | Verificações adicionais |
|---|---|---|---|---|
| 1 | GET /api/v1/championship | Autenticado | 200 | Retorna tabela do campeonato |
| 2 | GET /api/v1/championship | Sem auth | 401 | — |
| 3 | GET /api/v1/championship/{id} | ID existe | 200 | `data.points`, `data.team_id` |
| 4 | PATCH /api/v1/championship/{id} | Atualiza points | 200 | `data.points` atualizado |
| 5 | DELETE /api/v1/championship/{id} | ID existe | 200 | Deletado |
| 6 | POST /api/v1/championship | team_id inválido | 422 | `errors.team_id` |

---

### 9.5 Championship Match Endpoints

**Arquivo:** `tests/Feature/Http/ChampionshipMatchTest.php`

| # | Endpoint | Cenário | Status | Verificações adicionais |
|---|---|---|---|---|
| 1 | GET /api/v1/championship-match | Autenticado | 200 | Lista partidas |
| 2 | POST /api/v1/championship-match | Dados válidos | 201 | is_ended == false por padrão |
| 3 | POST /api/v1/championship-match | home == away team | 422 | `errors.home_team_id` ou similar |
| 4 | POST /api/v1/championship-match | Data formato inválido | 422 | `errors.date` |
| 5 | POST /api/v1/championship-match | Time inexistente | 422 | `errors.away_team_id` |
| 6 | GET /api/v1/championship-match/{id} | ID existe | 200 | Dados corretos |
| 7 | PATCH /api/v1/championship-match/{id} | Atualiza gols, is_ended=false | 200 | Gols atualizados, evento não disparado |
| 8 | PATCH /api/v1/championship-match/{id} | is_ended = true | 200 | end_time preenchido; tabela do campeonato atualizada |
| 9 | PATCH /api/v1/championship-match/{id} | Partida já finalizada | 4xx | Erro de negócio |
| 10 | PATCH /api/v1/championship-match/{id} | Gols negativos | 422 | `errors.away_team_goals` |
| 11 | DELETE /api/v1/championship-match/{id} | ID existe | 200 | Deletado |
| 12 | GET /api/v1/championship-match | Sem auth | 401 | — |

---

## 10. Feature Tests — Fluxos de Negócio Integrados

Estes testes cobrem fluxos end-to-end usando o banco de dados real (SQLite in-memory), sem mocks de repositories.

---

### 10.1 Fluxo Completo: Campeonato de Ponta a Ponta

**Arquivo:** `tests/Feature/Flows/CampeonatoFlowTest.php`

#### `test_fluxo_completo_campeonato`

Passos:
1. Criar dois times (Flamengo e Palmeiras)
2. Verificar que duas entradas em `championships` foram criadas automaticamente (via observer)
3. Adicionar 2 jogadores a cada time
4. Criar partida entre os times (Flamengo vs Palmeiras, date: 2025-01-01, start_time: 15:00)
5. Verificar que partida foi criada com is_ended = false
6. Atualizar partida: away_team_goals=2, home_team_goals=0, is_ended=true
7. Verificar que:
   - `end_time` foi preenchido automaticamente
   - `is_ended == true`
   - Away team championship: points=3, number_of_victories=1, number_of_goals=2
   - Home team championship: points=0, number_of_defeats=1, number_of_goals=0

---

#### `test_empate_distribui_pontos_corretamente`

Passos:
1. Criar dois times
2. Criar e finalizar partida com gols iguais (1x1)
3. Verificar: ambos os times com points=1, victories=0, defeats=0, goals=1

---

#### `test_multiplas_partidas_acumulam_estatisticas`

Passos:
1. Criar dois times
2. Criar e finalizar 3 partidas:
   - Partida 1: Flamengo 2x0 Palmeiras
   - Partida 2: Palmeiras 3x1 Flamengo
   - Partida 3: Flamengo 1x1 Palmeiras
3. Verificar Flamengo: points=4, victories=1, defeats=1, goals=4
4. Verificar Palmeiras: points=4, victories=1, defeats=1, goals=4

---

### 10.2 Fluxo: Remoção em Cascata ao Deletar Time

**Arquivo:** `tests/Feature/Flows/TeamDeletionFlowTest.php`

#### `test_deletar_time_remove_todos_os_dados_associados`

Passos:
1. Criar time A com 3 jogadores
2. Criar time B
3. Criar 2 partidas envolvendo time A (1 como home, 1 como away)
4. Deletar time A
5. Verificar que:
   - Time A não existe em `teams`
   - Os 3 jogadores foram deletados de `players`
   - As 2 partidas foram deletadas de `championship_matchs`
   - O championship do time A foi deletado de `championships`
   - Time B e seus dados não foram afetados

---

### 10.3 Fluxo: Validação de Número de Camisa

**Arquivo:** `tests/Feature/Flows/PlayerShirtNumberTest.php`

| # | Método | Passos | Resultado |
|---|---|---|---|
| 1 | `test_numero_camisa_unico_por_time` | Cria jogador #10 no time A; tenta criar outro #10 no time A | Segundo retorna erro |
| 2 | `test_mesmo_numero_em_times_diferentes_e_permitido` | Cria jogador #10 no time A e #10 no time B | Ambos criados com sucesso |
| 3 | `test_update_aceita_mesmo_numero_para_o_proprio_jogador` | Cria jogador #10; atualiza passando number=10 novamente | Não deve retornar erro |

---

### 10.4 Fluxo: Observer cria Championship automaticamente

**Arquivo:** `tests/Feature/Flows/TeamObserverFlowTest.php`

| # | Método | Verificação |
|---|---|---|
| 1 | `test_championship_criado_ao_criar_time` | POST /api/v1/team → `championships` tem 1 registro com o team_id correto |
| 2 | `test_championship_deletado_ao_deletar_time` | DELETE /api/v1/team/{id} → `championships` não tem mais registro do time |
| 3 | `test_valores_iniciais_do_championship_sao_zero` | Championship criado: points=0, goals=0, victories=0, defeats=0 |

---

## 11. Resumo de Arquivos a Criar

```
tests/
├── TestCase.php                                          (modificar — adicionar helpers)
├── Unit/
│   ├── Services/
│   │   ├── Auth/
│   │   │   ├── LoginServiceTest.php                      (4 casos)
│   │   │   ├── RegisterServiceTest.php                   (4 casos)
│   │   │   ├── RefreshTokenServiceTest.php               (2 casos)
│   │   │   └── LogoutServiceTest.php                     (2 casos)
│   │   ├── Championship/
│   │   │   ├── UpdateChampionshipTableServiceTest.php    (8 casos — mais crítico)
│   │   │   └── ChampionshipServiceTest.php               (5 casos)
│   │   ├── ChampionshipMatch/
│   │   │   ├── UpdateChampionshipMatchServiceTest.php    (6 casos)
│   │   │   └── ChampionshipMatchServiceTest.php          (4 casos)
│   │   ├── Player/
│   │   │   └── PlayerServiceTest.php                     (7 casos)
│   │   ├── Team/
│   │   │   └── TeamServiceTest.php                       (5 casos)
│   │   ├── User/
│   │   │   └── UserServiceTest.php                       (5 casos)
│   │   └── Cognito/
│   │       └── CognitoClientTest.php                     (4 casos)
│   ├── Repositories/
│   │   ├── BaseRepositoryTest.php                        (12 casos)
│   │   └── ChampionshipMatchsRepositoryTest.php          (3 casos)
│   ├── Models/
│   │   └── ModelRelationsTest.php                        (10 casos)
│   ├── Guards/
│   │   └── CognitoGuardTest.php                          (5 casos)
│   ├── Observers/
│   │   └── TeamObserverTest.php                          (5 casos)
│   ├── Events/
│   │   └── EndOfTheMatchTest.php                         (1 caso)
│   └── Listeners/
│       └── UpdateChampionshipTableTest.php               (1 caso)
└── Feature/
    ├── Requests/
    │   ├── AuthRequestsTest.php                          (8 casos)
    │   ├── TeamRequestsTest.php                          (3 casos)
    │   ├── PlayerRequestsTest.php                        (4 casos)
    │   └── ChampionshipMatchRequestsTest.php             (6 casos)
    ├── Http/
    │   ├── AuthTest.php                                  (9 casos)
    │   ├── TeamTest.php                                  (11 casos)
    │   ├── PlayerTest.php                                (9 casos)
    │   ├── ChampionshipTest.php                          (6 casos)
    │   └── ChampionshipMatchTest.php                     (12 casos)
    └── Flows/
        ├── CampeonatoFlowTest.php                        (3 casos)
        ├── TeamDeletionFlowTest.php                      (1 caso)
        ├── PlayerShirtNumberTest.php                     (3 casos)
        └── TeamObserverFlowTest.php                      (3 casos)
```

---

## Contagem Total

| Categoria | Arquivos | Casos de Teste |
|---|---|---|
| Unit — Services | 9 arquivos | 45 casos |
| Unit — Repositories | 2 arquivos | 15 casos |
| Unit — Models | 1 arquivo | 10 casos |
| Unit — Guards | 1 arquivo | 5 casos |
| Unit — Observers | 1 arquivo | 5 casos |
| Unit — Events/Listeners | 2 arquivos | 2 casos |
| Feature — Form Requests | 4 arquivos | 21 casos |
| Feature — HTTP Endpoints | 5 arquivos | 47 casos |
| Feature — Flows | 4 arquivos | 10 casos |
| **Total** | **29 arquivos** | **~160 casos** |

---

## Prioridade de Implementação

### Alta (lógica de negócio crítica — implementar primeiro)
1. `UpdateChampionshipTableServiceTest` — cálculo de pontos
2. `UpdateChampionshipMatchServiceTest` — proteção de reedição + eventos
3. `PlayerServiceTest` — validação de número de camisa
4. `CampeonatoFlowTest` — fluxo principal integrado
5. `TeamObserverTest` + `TeamObserverFlowTest` — automação de championships

### Média (cobertura de CRUD e validações)
6. Todos os `Http/*Test.php` — respostas HTTP corretas
7. `AuthRequestsTest` + validações de FormRequests
8. `BaseRepositoryTest` — confiança na camada de dados

### Baixa (cobertura de detalhes)
9. `ModelRelationsTest` — relacionamentos Eloquent
10. `CognitoClientTest` — integração com JWT
11. `CognitoGuardTest` — autenticação customizada
12. Demais services CRUD (simples, baixo risco)
