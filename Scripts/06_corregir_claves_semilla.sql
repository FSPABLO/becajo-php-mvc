-- ============================================================================
-- Corrige el hash de contraseña de los dos usuarios de prueba.
--
-- 02_datos_semilla.sql traía un hash de relleno (nunca se generó uno real con
-- password_hash(), quedó pendiente para Persona 3). Reemplaza esas dos filas
-- por hashes bcrypt reales: ana.alfaro entra con "auditor2026" y luis.rojas
-- con "adminbd2026" — las mismas contraseñas ya documentadas en CLAUDE.md.
--
-- Re-ejecutable: solo actualiza, no inserta, así que correrlo dos veces no
-- hace daño. Quien cargue el esquema desde cero a partir de ahora no lo
-- necesita, porque 02_datos_semilla.sql ya quedó con el hash correcto — este
-- script es solo para quien ya había cargado la semilla con el hash viejo.
-- ============================================================================

UPDATE usuario
   SET contrasena_hash = '$2b$10$6jLfYz.BZ6HHdAWE7zQIMuwWcAtBD60mG7AiWwVb8iU2jm7RV4/mW'
 WHERE correo = 'ana.alfaro@consultora.example';

UPDATE usuario
   SET contrasena_hash = '$2b$10$/wt647dEM/D2h.VVkFfE0uMaKq1qA.bLDPTtuXykIs59maS1VzdAW'
 WHERE correo = 'luis.rojas@empresa.example';

COMMIT;
