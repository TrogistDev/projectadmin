# Diagrama de Entidade-Relacionamento (DER)

```mermaid
erDiagram
    USUARIOS {
        int id PK
        varchar nombre
        varchar apellidos
        varchar correo UNIQUE
        varchar contrasena
        enum rol
        varchar departamento
    }

    PROYECTOS {
        int id PK
        varchar nombre UNIQUE
        text descripcion
        date fecha_inicio
        date fecha_entrega
        enum estado
        int responsable_id FK
        int porcentaje_avance
    }

    FASES {
        int id PK
        varchar nombre
        text descripcion
        int orden
        bool completada
        datetime fecha_completado
        int proyecto_id FK
    }

    PROYECTO_MIEMBRO {
        int proyecto_id FK
        int usuario_id FK
        datetime fecha_add
        varchar rol_especifico
    }

    USUARIOS ||--o{ PROYECTOS : responsable
    PROYECTOS ||--|{ FASES : contiene
    USUARIOS }|--|{ PROYECTO_MIEMBRO : participa
    PROYECTOS }|--|{ PROYECTO_MIEMBRO : tiene_miembros
```

## Descrição das relações

- `USUARIOS` e `PROYECTOS` são relacionados pela chave estrangeira `responsable_id`.
- `PROYECTOS` e `FASES` têm relação 1:N para permitir fases ordenáveis dentro de cada projeto.
- A tabela `PROYECTO_MIEMBRO` representa a relação N:M entre projetos e usuários.
- A tabela intermediária `PROYECTO_MIEMBRO` contém metadados:
  - `fecha_add`: data de inclusão do usuário no projeto
  - `rol_especifico`: função do usuário no projeto (analista, desarrollador, tester, etc.)
