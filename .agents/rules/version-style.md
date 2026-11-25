---
trigger: always_on
glob:
description:
---

Implementa un sistema de versionado para todo el proyecto utilizando el siguiente formato:

**`AAAA-MM-CAMBIO`**

Ejemplo:
- `2026-11-01`: Primera versión o cambio inicial de noviembre de 2026.
- `2026-11-02`: Segundo cambio de noviembre de 2026.
- `2026-11-03`: Tercer cambio de noviembre de 2026.
- `2026-12-01`: Primer cambio de diciembre de 2026.

### Reglas de versionado

1. El formato obligatorio será `2026-mes-cambio`, utilizando el año y mes actuales dinámicamente.
2. El número de cambio debe iniciar en `01` cada mes.
3. Cada nueva actualización funcional debe incrementar el número de cambio.
4. Si cambia el mes, el contador debe reiniciarse en `01`.
5. No sobrescribir versiones anteriores; conservar un historial de versiones.
6. Registrar cada versión con:
   - Número de versión.
   - Fecha y hora.
   - Descripción de los cambios.
   - Archivos o módulos modificados.
   - Responsable del cambio, si está disponible.
7. Implementar un archivo centralizado de control de versiones (`VERSION` o equivalente).
8. Mostrar la versión actual del sistema en el panel principal o en el apartado Acerca de.
9. Evitar generar nuevas versiones por simples consultas, visualizaciones o ejecuciones sin cambios en el código.
10. Antes de registrar una nueva versión, verificar la última versión existente para evitar duplicados.

### Importante
Analiza primero la arquitectura actual del proyecto y adapta el sistema de versionado a la tecnología utilizada. No modifiques funcionalidades existentes ni elimines información histórica.

El objetivo es tener un control ordenado, automático y trazable de todas las actualizaciones del sistema.