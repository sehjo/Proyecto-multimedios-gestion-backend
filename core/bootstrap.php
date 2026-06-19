<?php

// Carga manual de clases, en orden de dependencia. Sin Composer ni autoload.

require_once __DIR__ . '/../config/config.php';

require_once __DIR__ . '/Conexion.php';
require_once __DIR__ . '/Request.php';
require_once __DIR__ . '/Response.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Validator.php';
require_once __DIR__ . '/Paginator.php';
require_once __DIR__ . '/Mailer.php';

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserType.php';
require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Priority.php';
require_once __DIR__ . '/../models/Disease.php';
require_once __DIR__ . '/../models/Drug.php';
require_once __DIR__ . '/../models/Diagnosis.php';
require_once __DIR__ . '/../models/DiseaseTreatment.php';
require_once __DIR__ . '/../models/DiagnosisTreatment.php';

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/UserTypeRepository.php';
require_once __DIR__ . '/../repositories/PatientRepository.php';
require_once __DIR__ . '/../repositories/TokenRepository.php';
require_once __DIR__ . '/../repositories/PasswordResetRepository.php';
require_once __DIR__ . '/../repositories/PriorityRepository.php';
require_once __DIR__ . '/../repositories/DiseaseRepository.php';
require_once __DIR__ . '/../repositories/DrugRepository.php';
require_once __DIR__ . '/../repositories/DiagnosisRepository.php';
require_once __DIR__ . '/../repositories/DiseaseTreatmentRepository.php';
require_once __DIR__ . '/../repositories/DiagnosisTreatmentRepository.php';

require_once __DIR__ . '/Auth.php';

require_once __DIR__ . '/../resources/UserResource.php';
require_once __DIR__ . '/../resources/UsersTypeResource.php';
require_once __DIR__ . '/../resources/PatientResource.php';

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/UsersTypeController.php';
require_once __DIR__ . '/../controllers/PatientController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/HealthController.php';
