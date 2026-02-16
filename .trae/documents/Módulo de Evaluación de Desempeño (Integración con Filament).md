## Garantías de Conservación de Datos
- No se borrará, actualizará ni migrará información existente en tablas actuales (Users, Departments, Surveys, Tasks, Nóminas, Ausencias, Vacaciones, etc.).
- Todas las operaciones serán aditivas: solo nuevas tablas y nuevos registros en ellas.
- Sin `cascade delete` ni triggers sobre tablas actuales; las nuevas FKs usarán `restrict` o `set null`.
- El módulo es independiente: lee datos existentes (jerarquías, usuarios, departamentos) sin escribir sobre ellos.

## Módulos y Tablas Nuevas (Aditivas)
- EvaluationCycle
- StrategicObjective
- EmployeeObjective
- ObjectiveCheckin
- EvaluationResult
- PerformanceRange
- BonusRule
- (Opcional) ObjectiveEvidence (si se requiere almacenar múltiples archivos por check-in)

## Recursos Filament Nuevos
- CycleResource: define periodos, reglas y objetivos estratégicos.
- StrategicObjectiveResource: gestión dentro del ciclo.
- EmployeeObjectiveResource: creación por empleado (3–5) y revisión por supervisor.
- ObjectiveCheckinRelationManager: avances y estados (approved/rejected_with_correction/incumplido).
- EvaluationResultResource: cálculo, rangos y bono.
- Integración en Nóminas: vista embebida de objetivos/resultados (solo lectura del EmployeeProfile existente).

## Reutilización (Solo Lectura / Referencia)
- Jerarquías: Users↔Departments y department_supervisor para visibilidad.
- Evidencias: si se vinculan Tasks/Surveys, se guardará solo el `task_id`/`survey_id` en tablas nuevas (no se modifican Task/Survey existentes).
- Patrón de aprobación: se replica la lógica de estados de Ausencias, sin tocar sus registros.

## Flujo Operativo
- Definición: empleado crea y envía objetivos; supervisor aprueba/rechaza con motivo.
- Seguimiento: registro por periodos; cuantitativo (valor numérico), cualitativo (narrativa/evidencias). 
- Revisión: approved, rejected_with_correction, incumplido (cualitativos sin corrección).
- Evaluación: cálculo ponderado → rango → bono.

## Cálculo y Configuración
- Cuantitativo: cumplimiento = 100 * último_valor/meta.
- Cualitativo: cumplimiento = 100 * aprobados/esperados.
- Final: sumatoria ponderada; rangos configurables; bono vía BonusRule.
- Base del bono: configurable por ciclo/BonusRule (no requiere editar Nómina).

## Permisos y Seguridad
- Roles actuales (HR, Supervisor, Colaborador, Super Admin) con permisos nuevos por recurso; sin modificar registros de roles existentes.

## Notificaciones y Scheduler
- Recordatorios de ventanas de definición/seguimiento (Notifications) y tareas programadas; sin afectar datos presentes.

## Pruebas y Verificación de No-Alteración
- Antes y después de migraciones: aserciones de conteo en tablas existentes (no varía).
- Pruebas de políticas/visibilidad y fórmulas de cálculo.
- Seeder de demo aislado (solo inserta en tablas nuevas).

¿Confirmas este plan de integración 100% no intrusiva para proceder a la implementación?