---
description: Publica una nueva versión del plugin (AAAA-MM-NN), actualiza VERSION, piruimgconvert.php y CHANGELOG.md, valida sintaxis PHP y ejecuta commit, tag y push en Git.
---

# Workflow de Publicación de Nueva Versión (`AAAA-MM-NN`)

Ejecuta este workflow en orden estricto para publicar una nueva versión del plugin **ImgConvert** manteniendo sincronizados `VERSION`, `piruimgconvert.php`, `CHANGELOG.md` y el repositorio Git.

---

## Paso 1: Verificación previa y análisis de cambios

1. Comprobar el estado del repositorio, la rama actual y la sincronización con el remoto:
   ```powershell
   git status --short; git branch --show-current; git fetch --quiet; git status -sb
   ```
2. **Condiciones de parada inmediata**:
   - Si no hay cambios funcionales ni de código en el proyecto, **detener el workflow** (no generar versiones vacías ni por simples consultas).
   - Si la rama local está detrás del remoto (`behind`), **detener el workflow** y avisar al usuario antes de modificar archivos.
   - Si no hay repositorio remoto configurado, continuar solo hasta el commit o avisar antes del push.
3. Revisar las diferencias reales con `git diff` (y `git diff --cached` si hay cambios en staging) para clasificar con precisión:
   - Nuevas funcionalidades (`Added`)
   - Mejoras o modificaciones (`Changed`)
   - Correcciones de errores (`Fixed`)
   - Elementos eliminados (`Removed`)
   - Mejoras de seguridad (`Security`)
4. Identificar archivos que **nunca** deben incluirse: `.env`, credenciales, tokens, archivos temporales, cachés o carpetas locales no deseadas (por ejemplo `.agents/` salvo indicación explícita).

---

## Paso 2: Calcular la nueva versión (`AAAA-MM-NN`)

1. Obtener la fecha y hora actuales del sistema (`AAAA-MM-DD HH:MM`) y el año-mes actual (`AAAA-MM`).
2. Consultar la última versión existente revisando (en orden):
   - Archivo `VERSION` (si existe en la raíz).
   - Última entrada en `CHANGELOG.md` (si existe).
   - Último tag de Git (`git tag --sort=-creatordate`).
3. Aplicar la regla de cálculo:
   - Si la última versión pertenece al **mismo `AAAA-MM`**, incrementar `NN` en `+1` con dos dígitos (`01` → `02`, `09` → `10`).
   - Si es un **mes o año nuevo** (o no existe versión previa con formato `AAAA-MM-NN`), iniciar en `AAAA-MM-01`.
4. Verificar que la nueva versión no exista ya en `VERSION`, `CHANGELOG.md` ni en `git tag` para evitar duplicados.

---

## Paso 3: Sincronizar la versión en el proyecto

Actualizar la versión en **todos** los puntos centralizados sin alterar funcionalidades:

1. **`VERSION`** (raíz del plugin): crear o actualizar con la nueva versión (ej. `2026-11-01`).
2. **`piruimgconvert.php`**:
   - Encabezado del plugin: `* Version:     AAAA-MM-NN`
   - Constante PHP: `define( 'PIRUIMGCONVERT_VERSION', 'AAAA-MM-NN' );`
   *(Esto también actualiza automáticamente la versión mostrada en el panel de administración y la caché de assets).*

---

## Paso 4: Actualizar `CHANGELOG.md`

1. Si `CHANGELOG.md` no existe, crearlo con encabezado `# Changelog` y descripción breve del formato `AAAA-MM-NN`.
2. Insertar la nueva versión **al inicio** del historial (sin sobrescribir ni borrar versiones anteriores) con esta estructura:

```markdown
## [AAAA-MM-NN] - AAAA-MM-DD HH:MM

- **Responsable:** <nombre obtenido de `git config user.name`>
- **Archivos modificados:** `ruta/archivo1.php`, `ruta/archivo2.css`

### Added
- Descripción real del cambio.

### Changed
- Descripción real del cambio.

### Fixed
- Descripción real del cambio.
```

*(Incluir solo las subsecciones que tengan cambios reales: `Added`, `Changed`, `Fixed`, `Removed`, `Security`. No inventar ni exagerar cambios).*

---

## Paso 5: Validación técnica obligatoria

Ejecutar las validaciones antes de hacer staging o commit:

1. **Sintaxis PHP** en todos los archivos del plugin:
   ```powershell
   Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
   ```
2. **Consistencia de versión**: confirmar que `VERSION`, el header `Version:` y `PIRUIMGCONVERT_VERSION` en `piruimgconvert.php`, y el encabezado más reciente de `CHANGELOG.md` contienen exactamente la misma versión `AAAA-MM-NN`.
3. **Si alguna validación falla**, detener el proceso de inmediato, reportar el error y **no** ejecutar commit ni push.

---

## Paso 6: Staging, Commit, Tag y Push

1. **Git Add selectivo**: agregar únicamente los archivos verificados del proyecto junto con `VERSION`, `piruimgconvert.php` y `CHANGELOG.md`.
   - **Prohibido** usar `git add .` o `git add -A` sin inspección previa.
   - Revisar `git status --short` tras el staging para confirmar que no haya archivos sensibles o indeseados.
2. **Git Commit**: usar un mensaje claro con el formato:
   ```text
   release: AAAA-MM-NN - resumen breve de los cambios
   ```
3. **Git Tag**: crear un tag anotado para trazabilidad:
   ```powershell
   git tag -a vAAAA-MM-NN -m "Release AAAA-MM-NN"
   ```
4. **Git Push**:
   - Confirmar la rama actual y subir la rama junto con el tag:
     ```powershell
     git push origin <rama-actual> --follow-tags
     ```
   - **Nunca** cambiar de rama automáticamente, **nunca** usar `--force`, **nunca** ejecutar `git reset --hard` ni reescribir historial.
   - Si el push falla por conflictos o rechazo del remoto, detenerse y reportarlo sin forzar.

---

## Paso 7: Resumen final

Presentar al usuario un resumen compacto con:

| Campo | Valor |
| --- | --- |
| **Versión anterior** | `...` |
| **Nueva versión** | `AAAA-MM-NN` |
| **Fecha y hora** | `AAAA-MM-DD HH:MM` |
| **Validaciones (`php -l`)** | OK / Error |
| **Rama** | `<rama>` |
| **Commit** | `<hash corto>` |
| **Tag** | `vAAAA-MM-NN` |
| **Estado del push** | Subido / Pendiente |

Seguido de una lista breve (2–5 puntos) con los cambios incluidos en la release.
