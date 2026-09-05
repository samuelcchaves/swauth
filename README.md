# sw auth

Módulo de autenticação e autorização, construído como pacote Composer independente (PSR-4) pensado para ser reutilizado em múltiplos projetos e ser atualizado de forma independente.

## Objetivos funcionais do projeto

- RBAC hierárquico por nivel (admin, manager, user, viewer)
- Autenticação com hashing Argon2id
- MFA via TOTP (Time-based One Time Password), com códigos de backup
- Gestão de sessões por hash de token
- Lockout de conta por tentativas falhadas, com auditoria completa
- Reset de password e verificação de e-mail, com controlo de estado por token

## Instalação

\`\`\`bash
composer require seeware/sw-auth
\`\`\`