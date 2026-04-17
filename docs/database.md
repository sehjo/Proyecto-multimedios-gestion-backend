# Estructura de Base de Datos y Seeders

El proyecto consta de entidades muy marcadas para simular el control y registro de un consultorio o caja de seguro. Todas las tablas en la base de datos están interrelacionadas a través de claves foráneas.

## Eliminación de Registros (Hard Deletes)

En esta API **no** se están utilizando "Soft Deletes". Todas las eliminaciones disparadas a través de los endpoints o controladores (`->delete()`) resultarán en un borrado físico permanente de la fila en MySQL para liberar espacio. 

Se debe tener cuidado al borrar ya que pueden existir posibles validaciones de restricciones limitando el borrado de padres que tengan hijos referenciados.

## Entidades Principales

1. **Usuarios y Tipos**
   - `users`: Datos de acceso y perfil (Médicos, Enfermeros, etc.)
   - `users_types`: Catálogo del tipo de perfil.

2. **Catálogos Clínicos**
   - `priority`: Catálogo de prioridad de las enfermedades (Baja, Media, Alta, Crítica).
   - `drugs`: Base de medicamentos disponibles.
   - `disease`: Enfermedades maestras (ej. Asma, Diabetes).

3. **Pacientes y Diagnósticos**
   - `patient`: Datos del paciente, que sufren de alguna enfermedad primaria y tienen un usuario responsable del registro.
   - `diagnoses`: Instancias en las cuales a un paciente se le dio una revisión o se le formuló una enfermedad. Atendido por un doctor.

4. **Intersecciones de Tratamientos (Pivotes)**
   - `disease_has_treatments`: Tratamientos prototípicos ligados a una enfermedad (Ej. qué drogas se recomiendan usualmente para el Asma).
   - `diagnoses_has_treatments`: Lo que realmente se le recetó al paciente diagnosticado en esa visita específica en su diagnóstico.

---

## Seeders (Datos Iniciales en Español)

Al correr `php artisan migrate:fresh --seed`, se insertará información de muestra completamente en **Español** para que puedas utilizar la plataforma de inmediato sin tener que registrar data manual.

Los seeders se ejecutan en orden estricto de acuerdo a las dependencias (`DatabaseSeeder.php`).

### Credenciales de Acceso Sembradas

El `UserSeeder` genera las siguientes cuentas (la contraseña es global para facilitar el testing):

| Rol | Email | Contraseña |
| --- | --- | --- |
| **Administrador** | `admin@ccss.cr` | `Admin1234!` |
| **Médico 1** | `doctor1@ccss.cr` | `Doctor1234!` |
| **Médico 2** | `doctor2@ccss.cr` | `Doctor1234!` |
| **Enfermero** | `nurse1@ccss.cr` | `Nurse1234!` |

### Enfermedades y Tipos sembrados:
- Resfriado común, Influenza, Diabetes tipo 2, Hipertensión, Asma, Infarto de miocardio.
- Medicamentos comunes: Paracetamol, Ibuprofen, Amoxicillin, Salbutamol, Omeprazole, Metformin, Diphenhydramine, Insulin.