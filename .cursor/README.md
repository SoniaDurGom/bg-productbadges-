# Configuración de Cursor en este repositorio

Este directorio documenta **qué hay versionado en el repo** respecto a Cursor y qué queda fuera a propósito.

## Qué contiene `.cursor/`


| Elemento                              | Estado en este repo             |
| ------------------------------------- | ------------------------------- |
| `README.md` (este fichero)            | Documentación del uso de Cursor |
| Reglas de proyecto (`.cursor/rules/`) | No utilizado                    |
| Skills (`.cursor/skills/`)            | No utilizado                    |
| Comandos slash (`.cursor/commands/`)  | No utilizado                    |
| `settings.json` del workspace         | No utilizado                    |


El desarrollo del módulo **productbadges** se realizó principalmente con **Cursor** como editor, usando el agente en modo conversación sobre el código del módulo PrestaShop 1.7.8.x.

## Configuración utilizada

- **Editor:** Cursor (configuración global del usuario en la máquina local).
- **Instrucciones a nivel proyecto:** no se añadieron `AGENTS.md`, `CLAUDE.md` ni reglas en `.cursor/rules/` dentro del repositorio.
- **Modelo / agente:** asistente integrado de Cursor (conversaciones largas por funcionalidad: MVP, multilang, frontend, multitienda, UX de badges).
- **Detalle del flujo de trabajo con IA:** ver `[IA.md](../IA.md)` en la raíz del repositorio.

## Qué no se incluye en Git

Cursor genera metadatos **locales y temporales** que no forman parte del módulo ni deben publicarse:

- Transcripts de agentes (`~/.cursor/projects/.../agent-transcripts/`)
- Terminales, cachés de herramientas y assets de capturas de pantalla del IDE
- Configuración global de usuario (`settings.json`, reglas personales, MCPs habilitados en el equipo)

## Relación con PrestaShop

El código del módulo vive en `modules/productbadges/`. Este directorio `.cursor/` **no afecta** a la instalación en PrestaShop: solo sirve para transparencia técnica sobre el uso de IA en el ejercicio.