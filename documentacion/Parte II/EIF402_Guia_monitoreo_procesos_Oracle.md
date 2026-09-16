# Guía para construir un sistema de monitoreo de procesos de Oracle

**UNIVERSIDAD NACIONAL — Escuela de Informática**
**Curso:** Administración de Bases de Datos (EIF402)

> Documento del curso, transcrito sin alterar su contenido. Los pasos venían
> enumerados de corrido en el original; aquí se numeran y se separan para poder
> referenciarlos desde el resto de la documentación del proyecto.
> Ubicación en el repositorio: `documentacion/Parte II/EIF402_Guia_monitoreo_procesos_Oracle.md`.
> El plan de la parte 2 rastrea sus catorce pasos uno a uno en su §2.5.

---

## 1. Identificar los procesos importantes

Determinar cuáles procesos de Oracle se van a monitorear, por ejemplo, DBWn,
LGWR, SMON, PMON y CKPT.

## 2. Definir qué queremos conocer de cada proceso

Establecer qué aspectos nos interesa vigilar: actividad, rendimiento, consumo de
recursos, tiempos de respuesta, errores, etc.

## 3. Seleccionar las métricas

Para cada aspecto, definir una medida que permita saber cómo está funcionando el
proceso.

## 4. Identificar dónde obtener las métricas

Determinar qué información proporciona Oracle y dónde puede obtenerse.

## 5. Realizar mediciones normales

Observar el sistema cuando funciona correctamente para conocer su comportamiento
habitual.

## 6. Crear una línea base

Utilizar esas mediciones normales como referencia para saber qué comportamiento
podemos considerar aceptable.

## 7. Definir umbrales

Establecer tres niveles, por ejemplo Normal, Advertencia, Crítico.

## 8. Automatizar la medición

Hacer que el sistema obtenga periódicamente las métricas sin que una persona
tenga que hacerlo manualmente, comparar los valores obtenidos; cada nueva
medición se compara con los umbrales establecidos.

## 9. Generar alertas

Cuando una métrica salga de los valores normales, generar una alerta que indique
que debe revisarse.

## 10. Guardar un historial

Conservar las mediciones para poder observar la evolución del sistema a través
del tiempo.

## 11. Relacionar las alertas

Analizar si varios problemas están relacionados y pueden tener una misma causa.

## 12. Presentar la información

Mostrar los resultados de manera sencilla, por ejemplo, mediante indicadores,
gráficos o un tablero.

## 13. Analizar y corregir

Cuando aparezca un problema, utilizar la información recopilada para determinar
qué está ocurriendo y tomar una acción.

## 14. Revisar y ajustar el monitoreo

Con el tiempo, revisar las métricas y los umbrales para mejorar el sistema de
monitoreo.
